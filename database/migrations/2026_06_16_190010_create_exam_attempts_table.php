<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Exam tokens (auto-generated codes)
        Schema::create('exam_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exam_room_id')->nullable()->constrained()->nullOnDelete();
            $table->string('token', 10); // 6-8 char alphanumeric
            $table->dateTime('issued_at');
            $table->dateTime('expires_at');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->unique(['token']);
            $table->index(['exam_session_id', 'is_active']);
        });

        // Student exam attempts (one row per attempt)
        Schema::create('exam_attempts', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained()->cascadeOnDelete();
            $table->foreignId('exam_session_id')->constrained()->cascadeOnDelete();
            $table->foreignId('student_id')->constrained()->cascadeOnDelete();
            $table->string('participant_number', 20)->nullable();
            $table->string('status', 20)->default('in_progress'); // in_progress, submitted, graded, expired
            $table->dateTime('started_at');
            $table->dateTime('submitted_at')->nullable();
            $table->dateTime('ends_at'); // deadline
            $table->integer('time_remaining_seconds')->nullable(); // cached for resume
            $table->decimal('score', 7, 2)->nullable();
            $table->decimal('score_auto', 7, 2)->default(0); // auto-graded
            $table->decimal('score_manual', 7, 2)->default(0); // manually graded
            $table->decimal('percentage', 5, 2)->nullable();
            $table->boolean('is_passed')->nullable();
            $table->integer('violation_count')->default(0); // tab switches, etc
            $table->ipAddress('ip_address')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();
            $table->index(['exam_id', 'student_id']);
            $table->index('status');
        });

        // Student answers (per question)
        Schema::create('exam_answers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_attempt_id')->constrained()->cascadeOnDelete();
            $table->foreignId('question_id')->constrained()->cascadeOnDelete();
            $table->json('answer_data')->nullable(); // for MC: option_id(s); matching: pairs; essay: text
            $table->text('essay_text')->nullable(); // separate for readability
            $table->boolean('is_correct')->nullable(); // null = pending manual grading
            $table->decimal('score', 5, 2)->default(0);
            $table->boolean('is_graded')->default(false);
            $table->foreignId('graded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->dateTime('graded_at')->nullable();
            $table->text('grading_notes')->nullable();
            $table->dateTime('answered_at')->nullable();
            $table->timestamps();
            $table->unique(['exam_attempt_id', 'question_id']);
        });

        // Violation log
        Schema::create('exam_violations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_attempt_id')->constrained()->cascadeOnDelete();
            $table->string('type', 50); // tab_switch, fullscreen_exit, devtools, etc
            $table->text('details')->nullable();
            $table->ipAddress('ip_address')->nullable();
            $table->timestamps();
            $table->index('exam_attempt_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_violations');
        Schema::dropIfExists('exam_answers');
        Schema::dropIfExists('exam_attempts');
        Schema::dropIfExists('exam_tokens');
    }
};
