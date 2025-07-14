<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateLeaveRequestsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('leave_requests', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('admin_user_id')->comment('Nhân viên xin nghỉ');
            $table->date('start_date')->comment('Ngày bắt đầu nghỉ');
            $table->date('end_date')->comment('Ngày kết thúc nghỉ');
            $table->text('reason')->comment('Lý do nghỉ');
            $table->string('attachment_file')->nullable()->comment('File đính kèm');
            $table->enum('status', ['pending', 'approved', 'rejected', 'cancelled'])->default('pending')->comment('Trạng thái đơn');
            $table->text('admin_notes')->nullable()->comment('Ghi chú của admin');
            $table->unsignedInteger('approved_by')->nullable()->comment('Admin duyệt');
            $table->timestamp('approved_at')->nullable()->comment('Thời gian duyệt');
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('admin_user_id')->references('id')->on('admin_users')->onDelete('cascade');
            $table->foreign('approved_by')->references('id')->on('admin_users')->onDelete('set null');
            
            // Indexes
            $table->index(['admin_user_id', 'status']);
            $table->index(['start_date', 'end_date']);
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
        Schema::dropIfExists('leave_requests');
    }
}