<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShipmentTasksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shipment_tasks', function (Blueprint $table) {
            $table->id();
            
            // Liên kết với bảng pos_orders.shipment_id (unique)
            $table->string('shipment_id', 255)->unique()->comment('ID vận đơn liên kết với pos_orders');
            
            // Status với các giá trị: wait, running, done
            $table->enum('status', ['wait', 'running', 'done'])->default('wait')->comment('Trạng thái task: wait, running, done');
            
            $table->timestamps();
            
            // Tạo index cho status để tối ưu query
            $table->index('status', 'idx_status');
            
            // Composite index cho status + created_at (để filter và sort)
            $table->index(['status', 'created_at'], 'idx_status_created_at');
            
            // Index cho updated_at để track thời gian cập nhật gần nhất
            $table->index('updated_at', 'idx_updated_at');
            
            // Foreign key constraint với pos_orders.shipment_id
            $table->foreign('shipment_id')
                  ->references('shipment_id')
                  ->on('pos_orders')
                  ->onDelete('cascade')
                  ->name('fk_shipment_tasks_shipment_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('shipment_tasks');
    }
}