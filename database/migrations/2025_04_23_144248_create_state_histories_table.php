<?php

use App\State;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('state_histories', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('sticker_id')->constrained('stickers')->cascadeOnDelete();
            $table->enum('state', array_column(State::cases(), 'value'))->default(State::EXISTS);
            $table->date('last_seen')->useCurrent();
            $table->timestamps();
        });

        // Migrate existing state + last_seen data into state_histories
        DB::table('stickers')->select('id', 'state', 'last_seen')->chunkById(100, function ($stickers) {
            foreach ($stickers as $sticker) {
                DB::table('state_histories')->insert([
                    'id' => Str::uuid(),
                    'sticker_id' => $sticker->id,
                    'state' => $sticker->state,
                    'last_seen' => $sticker->last_seen,
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            }
        });

        Schema::table('stickers', function (Blueprint $table) {
            if (Schema::hasColumn('stickers', 'state')) {
                $table->dropColumn('state');
            }
            if (Schema::hasColumn('stickers', 'last_seen')) {
                $table->dropColumn('last_seen');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('stickers', function (Blueprint $table) {
            $table->enum('state', array_column(State::cases(), 'value'))->default(State::EXISTS->value);
            $table->date('last_seen')->useCurrent();
        });

        // Restore data from latest state history into the stickers table
        DB::table('stickers')->select('id')->chunkById(100, function ($stickers) {
            foreach ($stickers as $sticker) {
                $latest = DB::table('state_histories')
                    ->where('sticker_id', $sticker->id)
                    ->orderByDesc('created_at')
                    ->first();

                if ($latest) {
                    DB::table('stickers')
                        ->where('id', $sticker->id)
                        ->update([
                            'state' => $latest->state,
                            'last_seen' => $latest->last_seen,
                        ]);
                }
            }
        });

        // Drop state_histories
        Schema::dropIfExists('state_histories');
    }
};
