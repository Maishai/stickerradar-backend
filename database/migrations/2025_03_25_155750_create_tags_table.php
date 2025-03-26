<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tags', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->foreignId('super_tag')->nullable();
            $table->string('color');
            $table->timestamps();
        });

        Schema::create('tag_sticker', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tag')->constrained()->onDelete('cascade');
            $table->foreignId('sticker')->constrained()->onDelete('cascade');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tags');
        Schema::dropIfExists('tag_sticker');
    }
};
