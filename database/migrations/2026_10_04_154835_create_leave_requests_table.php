<?php

use App\Enums\LeaveRequestStatusEnum;
use App\Enums\LeaveRequestTypeEnum;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained('users')->cascadeOnDelete();
            $table->enum('type', LeaveRequestTypeEnum::values());
            $table->enum('status', LeaveRequestStatusEnum::values())->default(LeaveRequestStatusEnum::Pending->value);
            $table->string('current_step', 32)->nullable();
            $table->string('key', 16)->unique();

            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->json('range_date')->nullable();

            $table->date('date')->nullable();
            $table->time('time_out')->nullable();
            $table->time('time_in')->nullable();
            $table->string('exit_reason', 255)->nullable();
            $table->string('destination', 128)->nullable();
            $table->string('contact_person', 64)->nullable();

            $table->time('estimated_arrival_time')->nullable();
            $table->string('late_reason', 255)->nullable();

            $table->string('notes', 255)->nullable();
            $table->string('attachment', 128)->nullable();

            $table->timestamp('requested_at')->useCurrent();
            $table->timestamp('approved_at')->nullable();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('rejected_at')->nullable();
            $table->foreignId('rejected_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('rejected_notes', 255)->nullable();

            $table->timestamps();

            $table->index(['user_id', 'type', 'date']);
        });

        Schema::create('leave_request_approvals', function (Blueprint $table) {
            $table->id();
            $table->foreignId('leave_request_id')->constrained('leave_requests')->cascadeOnDelete();
            $table->string('step', 32);
            $table->enum('decision', ['pending', 'approved', 'rejected'])->default('pending');
            $table->foreignId('decided_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('decided_at')->nullable();
            $table->string('notes', 255)->nullable();
            $table->timestamps();

            $table->unique(['leave_request_id', 'step']);
            $table->index(['leave_request_id', 'decision']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_request_approvals');
        Schema::dropIfExists('leave_requests');
    }
};
