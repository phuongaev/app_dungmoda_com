<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateShipmentDeliveryContactsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('shipment_delivery_contacts', function (Blueprint $table) {
            $table->id();
            
            // Liên kết với bảng pos_orders
            $table->string('shipment_id', 255)->nullable()->comment('ID vận đơn liên kết với pos_orders');
            $table->string('order_id', 50)->comment('Mã đơn hàng liên kết với pos_orders');
            
            // Thông tin nhân viên giao hàng
            $table->string('delivery_phone', 20)->comment('Số điện thoại nhân viên giao hàng');
            $table->string('delivery_name', 100)->comment('Tên nhân viên giao hàng');
            
            $table->timestamps();
            
            // Tạo index để tối ưu performance khi tìm kiếm
            $table->index('shipment_id', 'idx_shipment_id');
            $table->index('order_id', 'idx_order_id');
            $table->index('delivery_phone', 'idx_delivery_phone');
            $table->index(['shipment_id', 'created_at'], 'idx_shipment_created_at');
            $table->index(['order_id', 'created_at'], 'idx_order_created_at');
            
            // Foreign key constraints với pos_orders
            // Liên kết với order_id (luôn có giá trị)
            $table->foreign('order_id')
                  ->references('order_id')
                  ->on('pos_orders')
                  ->onDelete('cascade')
                  ->name('fk_delivery_contacts_order_id');
                  
            // Liên kết với shipment_id (có thể nullable)
            // Chỉ tạo foreign key nếu shipment_id có giá trị
            $table->foreign('shipment_id')
                  ->references('shipment_id')
                  ->on('pos_orders')
                  ->onDelete('cascade')
                  ->name('fk_delivery_contacts_shipment_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('shipment_delivery_contacts');
    }
}