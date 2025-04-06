<?php

use App\Console\Commands\GenerateMissingThumbnails;
use Illuminate\Support\Facades\Schedule;

Schedule::command(GenerateMissingThumbnails::class)->everyFiveMinutes()->appendOutputTo(storage_path('logs/schedule.log'));
