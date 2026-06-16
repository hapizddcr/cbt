<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('question_banks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('creator_id')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->enum('level', ['easy', 'medium', 'hard'])->default('medium');
            $table->boolean('is_shared')->default(false); // shared across teachers
            $table->timestamps();
            $table->softDeletes();
            $table->index(['subject_id', 'is_shared']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_banks');
    }
};
