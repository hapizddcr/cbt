<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('exams', function (Blueprint $table) {
            $table->id();
            $table->foreignId('subject_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_bank_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('creator_id')->constrained('users')->cascadeOnDelete();
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('type', ['daily', 'midterm', 'final', 'tryout', 'quiz'])->default('quiz');
            $table->integer('duration_minutes'); // exam duration
            $table->integer('total_questions')->default(0);
            $table->decimal('max_score', 7, 2)->default(100);
            $table->decimal('passing_score', 7, 2)->default(75);
            $table->integer('question_per_page')->default(1); // 1 = one-by-one, all = show all
            $table->boolean('shuffle_questions')->default(true);
            $table->boolean('shuffle_options')->default(true);
            $table->boolean('show_result')->default(true); // show score after submit
            $table->boolean('show_answer')->default(false); // show correct answer in result
            $table->boolean('allow_review')->default(true); // allow review before submit
            $table->integer('max_attempts')->default(1);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();
            $table->index(['subject_id', 'is_active']);
        });

        // Selected questions per exam with per-question score override
        Schema::create('exam_questions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->integer('order')->default(0);
            $table->decimal('score', 5, 2)->default(1); // override per exam
            $table->timestamps();
            $table->unique(['exam_id', 'question_id']);
            $table->index('order');
        });

        // Exam sessions (sesi ujian - time slots)
        Schema::create('exam_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained()->cascadeOnDelete();
            $table->string('name'); // Sesi 1, Sesi 2
            $table->dateTime('start_time');
            $table->dateTime('end_time');
            $table->integer('duration_minutes'); // override or use exam default
            $table->integer('token_lifetime_minutes')->default(60); // token validity
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['exam_id', 'is_active']);
            $table->index('start_time');
        });

        // Room allocations
        Schema::create('exam_rooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_session_id')->constrained()->cascadeOnDelete();
            $table->string('name'); // Ruang A, Lab 1
            $table->integer('capacity');
            $table->foreignId('supervisor_id')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->index('exam_session_id');
        });

        // Student session allocations
        Schema::create('exam_session_student', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exam_room_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('participant_number', 20)->nullable(); // nomor peserta
            $table->integer('seat_number')->nullable();
            $table->timestamps();
            $table->unique(['exam_session_id', 'student_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_session_student');
        Schema::dropIfExists('exam_rooms');
        Schema::dropIfExists('exam_sessions');
        Schema::dropIfExists('exam_questions');
        Schema::dropIfExists('exams');
    }
};
