<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('presence_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('class_schedule_id')->constrained('class_schedules')->cascadeOnDelete();
            $table->foreignId('classroom_id')->constrained('classrooms')->cascadeOnDelete();
            $table->foreignId('teacher_id')->nullable()->constrained('teachers')->nullOnDelete();
            $table->date('date')->index();
            $table->string('status', 16)->default('open')->index();
            $table->string('current_key', 64)->nullable()->unique();
            $table->string('previous_key', 64)->nullable();
            $table->timestamp('qr_expires_at')->nullable();
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('closed_at')->nullable();
            $table->unsignedInteger('total_students')->default(0);
            $table->unsignedInteger('total_present')->default(0);
            $table->unsignedInteger('total_sick')->default(0);
            $table->unsignedInteger('total_permit')->default(0);
            $table->timestamps();

            $table->unique(['class_schedule_id', 'date']);
        });

        Schema::create('presence_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('presence_session_id')->constrained('presence_sessions')->cascadeOnDelete();
            $table->foreignId('student_id')->constrained('students')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->timestamp('scanned_at')->useCurrent();
            $table->decimal('latitude', 10, 7)->nullable();
            $table->decimal('longitude', 10, 7)->nullable();
            $table->boolean('inside_zone')->default(true);
            $table->timestamps();

            $table->unique(['presence_session_id', 'student_id']);
            $table->index(['student_id', 'scanned_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('presence_records');
        Schema::dropIfExists('presence_sessions');
    }
};
