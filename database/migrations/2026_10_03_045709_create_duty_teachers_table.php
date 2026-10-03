<?php

use App\Enums\DayEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('duty_teachers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('academic_year_id')->constrained('academic_years')->cascadeOnDelete();
            $table->foreignId('teacher_id')->constrained('teachers')->cascadeOnDelete();
            $table->enum('day', DayEnum::values());
            $table->time('start_time');
            $table->time('end_time');
            $table->timestamps();

            $table->unique(['academic_year_id', 'day', 'start_time', 'teacher_id'], 'duty_slot_teacher_unique');
            $table->index(['academic_year_id', 'day', 'start_time'], 'duty_slot_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('duty_teachers');
    }
};
