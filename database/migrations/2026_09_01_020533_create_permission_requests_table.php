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
        Schema::create('permission_requests', function (Blueprint $table) {
        $table->id();

        $table->foreignId('student_id')
        ->constrained('students')
        ->cascadeOnDelete();

        $table->enum('type', [
            'izin',
            'sakit',
        ]);

        $table->date('start_date');
        $table->date('end_date');

        $table->text('reason');

        $table->string('document_path');

        $table->enum('status', [
            'pending',
            'approved',
            'rejected',
        ])->default('pending');

        $table->foreignId('approved_by')
            ->nullable()
            ->constrained('teachers')
            ->nullOnDelete();

        $table->timestamp('approved_at')->nullable();

        $table->text('rejection_reason')->nullable();

        $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('permission_requests');
    }
};
