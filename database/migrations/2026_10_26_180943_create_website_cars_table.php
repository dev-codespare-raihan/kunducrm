<?php

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
        Schema::create('website_cars', function (Blueprint $table) {
            $table->id();
            $table->string('slug')->unique();
            $table->string('name');
            $table->string('type');
            $table->string('seats');
            $table->string('fuel');
            $table->string('local_label');
            $table->string('best_for');
            $table->string('image_path')->nullable();
            $table->string('image_url')->nullable();
            $table->string('image_alt');
            $table->unsignedInteger('image_width')->default(1200);
            $table->unsignedInteger('image_height')->default(675);
            $table->text('blurb');
            $table->unsignedInteger('sort_order')->default(0);
            $table->boolean('is_published')->default(true);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('website_cars');
    }
};
