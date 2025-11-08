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
        Schema::create('books', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->foreignId('category_id')->constrained('categories')->onDelete('restrict');
            $table->foreignId('author_id')->constrained('authors')->onDelete('restrict');
            $table->string('isbn')->unique();
            $table->string('publisher');
            $table->year('publication_year');
            $table->string('store_location');
            $table->enum('status', ['available', 'rented', 'reserved'])->default('available');
            $table->timestamps();

            // $table->foreign('category_id')->references('id')->on('categories')->onDelete('restrict');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
