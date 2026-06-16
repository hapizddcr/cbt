<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_bank_id')->constrained()->cascadeOnDelete();
            $table->enum('type', [
                'multiple_choice',  // PG (single answer)
                'complex_mc',       // PG Kompleks (multiple answers)
                'true_false',       // Benar/Salah
                'short_answer',     // Isian singkat
                'essay',            // Essai
                'matching',         // Menjodohkan
                'ordering',         // Urutan (improvement)
            ]);
            $table->text('content'); // Supports HTML + LaTeX
            $table->string('image_path')->nullable();
            $table->text('explanation')->nullable(); // pembahasan
            $table->decimal('default_score', 5, 2)->default(1); // default points
            $table->enum('difficulty', ['easy', 'medium', 'hard'])->default('medium');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['question_bank_id', 'type']);
        });

        // Options for multiple choice / true-false
        Schema::create('question_options', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->text('content');
            $table->string('image_path')->nullable();
            $table->boolean('is_correct')->default(false);
            $table->integer('order')->default(0);
            $table->timestamps();
            $table->index('question_id');
        });

        // Matching pairs (left-right)
        Schema::create('question_matching_pairs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->text('left_content');
            $table->text('right_content');
            $table->string('left_image_path')->nullable();
            $table->string('right_image_path')->nullable();
            $table->integer('order')->default(0);
            $table->timestamps();
            $table->index('question_id');
        });

        // Ordering items
        Schema::create('question_ordering_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->text('content');
            $table->integer('correct_order');
            $table->integer('display_order')->default(0);
            $table->timestamps();
            $table->index('question_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_ordering_items');
        Schema::dropIfExists('question_matching_pairs');
        Schema::dropIfExists('question_options');
        Schema::dropIfExists('questions');
    }
};
