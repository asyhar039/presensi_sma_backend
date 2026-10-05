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
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_requests');
    }
};
