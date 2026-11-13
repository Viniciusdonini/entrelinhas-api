<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('books', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('author_id')->constrained()->restrictOnDelete();
            $table->foreignId('genre_id')->nullable()->constrained()->restrictOnDelete();
            $table->foreignId('shelf_id')->nullable()->constrained()->restrictOnDelete();
            $table->string('title', 120);
            $table->unsignedInteger('total_pages');
            $table->unsignedInteger('current_page')->default(0);
            $table->string('status', 20)->default('wantToRead');
            $table->text('notes')->default('');
            $table->string('cover_url', 2048)->default('');
            $table->timestamps();
            $table->index(['user_id', 'status']);
            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('books');
    }
};
