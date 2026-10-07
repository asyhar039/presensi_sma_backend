<?php

use App\Enums\DayEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('class_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('classroom_id')->constrained('classrooms')->cascadeOnDelete();
            $table->enum('day', DayEnum::values());
            $table->unsignedTinyInteger('period');
            $table->time('start_time');
            $table->time('end_time');
            $table->foreignId('teacher_id')->nullable()->constrained('teachers')->nullOnDelete();
            $table->timestamps();

            $table->unique(['classroom_id', 'day', 'period', 'teacher_id'], 'class_sched_slot_teacher_unique');
            $table->index(['classroom_id', 'day', 'period'], 'class_sched_slot_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('class_schedules');
    }
};
