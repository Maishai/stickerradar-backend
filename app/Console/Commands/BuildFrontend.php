<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Process;
use Symfony\Component\Console\Attribute\AsCommand;

#[AsCommand(
    name: 'frontend:build',
    description: 'Clone, build Ionic frontend and symlink into public/app'
)]
class BuildFrontend extends Command
{
    public function handle(): int
    {
        $frontendDir = base_path('../stickerradar-frontend');
        $repoUrl = 'https://github.com/Maishai/stickerradar-frontend';

        if (! File::exists($frontendDir)) {
            if (! $this->confirm(
                "No repo found at {$frontendDir}. Clone it now?"
            )) {
                $this->info('Aborted.');

                return 1;
            }

            $this->info("Cloning {$repoUrl} into {$frontendDir}…");
            $clone = Process::run(
                "git clone {$repoUrl} {$frontendDir}"
            );
            if (! $clone->successful()) {
                $this->error(
                    "Git clone failed:\n".$clone->errorOutput()
                );

                return 1;
            }
        }

        $this->info(
            'Switching to master…'
        );
        $git = Process::path($frontendDir)
            ->run('git fetch --all && git switch master');
        if (! $git->successful()) {
            $this->error(
                "Git checkout failed:\n".$git->errorOutput()
            );

            return 1;
        }

        $this->info('Installing npm dependencies (npm ci)…');
        $npm = Process::path($frontendDir)
            ->run('npm ci');
        if (! $npm->successful()) {
            $this->error(
                "npm ci failed:\n".$npm->errorOutput()
            );

            return 1;
        }

        $this->info('Building Ionic app for production…');
        $build = Process::path($frontendDir)
            ->run(
                'npx ionic build --prod -- --base-href="/app/"'
            );
        if (! $build->successful()) {
            $this->error(
                "Ionic build failed:\n".$build->errorOutput()
            );

            return 1;
        }

        $buildOutput = "{$frontendDir}/www";
        $link = public_path('app');

        if (! File::exists($link)) {
            File::link($buildOutput, $link);
            $this->info("Symlinked public/app → {$buildOutput}");
        }

        return 0;
    }
}
