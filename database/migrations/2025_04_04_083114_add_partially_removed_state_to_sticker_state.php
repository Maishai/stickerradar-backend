<?php

use App\State;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('stickers', function (Blueprint $table) {
            $table->enum('state', array_column(State::cases(), 'value'))->default(State::EXISTS)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void {}
};
