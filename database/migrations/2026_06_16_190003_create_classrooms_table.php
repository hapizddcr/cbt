<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('classrooms', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained()->cascadeOnDelete();
            $table->string('name', 50); // X IPA 1
            $table->string('grade', 10); // X, XI, XII
            $table->string('major', 50)->nullable(); // IPA, IPS, Bahasa
            $table->foreignId('homeroom_teacher_id')->nullable()->constrained('users')->nullOnDelete();
            $table->integer('capacity')->default(36);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->index(['academic_year_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('classrooms');
    }
};
