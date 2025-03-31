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
            $table->uuid('id')->primary();
            $table->string('name')->unique();
            $table->uuid('super_tag')->nullable();
            $table->string('color');
            $table->timestamps();
        });

        Schema::table('tags', function (Blueprint $table) {
            $table->foreign('super_tag')->references('id')->on('tags')->onDelete('set null');
        });

        Schema::create('sticker_tag', function (Blueprint $table) {
            $table->foreignUuid('tag_id')->constrained()->onDelete('cascade');
            $table->foreignUuid('sticker_id')->constrained()->onDelete('cascade');
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
