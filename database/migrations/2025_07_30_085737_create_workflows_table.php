<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateWorkflowsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('workflows', function (Blueprint $table) {
            $table->id();
            
            // Workflow ID dạng string (giống như trong pos_order_workflow_histories)
            $table->string('workflow_id', 55)->unique()->comment('ID workflow từ n8n');
            
            // Tên workflow
            $table->string('workflow_name', 255)->comment('Tên workflow');
            
            // Trạng thái hoạt động của workflow
            $table->enum('workflow_status', ['active', 'deactive'])->default('active')->comment('Trạng thái hoạt động workflow');
            
            $table->timestamps();
            
            // Indexes
            $table->index('workflow_status', 'idx_workflow_status');
            $table->index(['workflow_status', 'created_at'], 'idx_status_created');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('workflows');
    }
}