<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShiftSwapRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shift_swap_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('requester_id')->comment('Người yêu cầu hoán đổi');
            $table->unsignedInteger('requester_shift_id')->comment('Ca trực của người yêu cầu');
            $table->unsignedInteger('target_user_id')->comment('Người được đề xuất hoán đổi');
            $table->unsignedInteger('target_shift_id')->comment('Ca trực của người được đề xuất');
            $table->text('reason')->comment('Lý do hoán đổi');
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending')->comment('Trạng thái đơn');
            $table->text('admin_notes')->nullable()->comment('Ghi chú của admin');
            $table->unsignedInteger('approved_by')->nullable()->comment('Admin duyệt');
            $table->timestamp('approved_at')->nullable()->comment('Thời gian duyệt');
            
            // Backup thông tin ca trực gốc để có thể khôi phục khi hủy
            $table->date('original_requester_shift_date')->comment('Ngày ca gốc của người yêu cầu');
            $table->date('original_target_shift_date')->comment('Ngày ca gốc của người được đề xuất');
            
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('requester_id')->references('id')->on('admin_users')->onDelete('cascade');
            $table->foreign('requester_shift_id')->references('id')->on('evening_shifts')->onDelete('cascade');
            $table->foreign('target_user_id')->references('id')->on('admin_users')->onDelete('cascade');
            $table->foreign('target_shift_id')->references('id')->on('evening_shifts')->onDelete('cascade');
            $table->foreign('approved_by')->references('id')->on('admin_users')->onDelete('set null');
            
            // Indexes
            $table->index(['requester_id', 'status']);
            $table->index(['target_user_id', 'status']);
            $table->index('status');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('shift_swap_requests');
    }
}