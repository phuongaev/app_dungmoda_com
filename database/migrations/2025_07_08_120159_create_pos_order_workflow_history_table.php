<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePosOrderWorkflowHistoryTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('pos_order_workflow_histories', function (Blueprint $table) {
            $table->id();
            
            // Foreign key tới pos_orders
            $table->unsignedBigInteger('pos_order_id')->comment('ID đơn hàng');
            
            // Foreign key tới base_statuses (workflow status)
            $table->unsignedInteger('workflow_status_id')->comment('ID trạng thái workflow từ base_statuses');
            
            // Workflow ID dạng varchar 55 ký tự - ví dụ: zXFNHnzvXzd3R1ui
            $table->string('workflow_id', 55)->nullable()->comment('ID workflow dạng string từ n8n');
            
            // Thời gian thực hiện workflow
            $table->timestamp('executed_at')->comment('Thời gian chạy workflow');
            
            $table->timestamps();
            
            // Foreign key constraints
            $table->foreign('pos_order_id')->references('id')->on('pos_orders')->onDelete('cascade');
            $table->foreign('workflow_status_id')->references('status_id')->on('base_statuses')->onDelete('cascade');
            
            // Indexes để tối ưu query
            $table->index(['pos_order_id', 'executed_at'], 'idx_order_executed_at');
            $table->index(['workflow_status_id', 'executed_at'], 'idx_status_executed_at');
            $table->index(['workflow_id', 'executed_at'], 'idx_workflow_executed_at');
            $table->index(['pos_order_id', 'workflow_status_id'], 'idx_order_status');
            $table->index(['pos_order_id', 'workflow_id'], 'idx_order_workflow');
            
            // Composite index để tối ưu filter không chạy workflow
            $table->index(['pos_order_id', 'workflow_id', 'executed_at'], 'idx_order_workflow_time');
            $table->index(['executed_at', 'workflow_id'], 'idx_time_workflow');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('pos_order_workflow_histories');
    }
}