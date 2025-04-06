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
        Schema::create('stickers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->double('lat');
            $table->double('lon');
            $table->enum('state', array_column(State::cases(), 'value'))->default(State::EXISTS);
            $table->date('last_seen')->useCurrent();
            $table->string('filename');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stickers');
    }
};
