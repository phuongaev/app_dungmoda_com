<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePosOrdersTable extends Migration
{
    public function up()
    {
        Schema::create('pos_orders', function (Blueprint $table) {
            $table->id();
            
            // Thông tin đơn hàng chính
            $table->string('order_id', 50)->unique()->comment('ID đơn hàng từ hệ thống POS');
            $table->unsignedInteger('system_id')->comment('ID hệ thống');
            $table->string('page_id', 50)->comment('ID trang Facebook/Shop');
            
            // Thông tin khách hàng - tối ưu cho tìm kiếm
            $table->string('customer_name', 100)->comment('Tên khách hàng');
            $table->string('customer_phone', 20)->index()->comment('SĐT khách hàng - có index');
            $table->uuid('customer_id')->nullable()->comment('UUID khách hàng');
            $table->string('customer_fb_id', 100)->nullable()->comment('Facebook ID khách hàng');
            
            // Thông tin đơn hàng
            $table->decimal('cod', 15, 2)->default(0)->comment('Tiền thu hộ');
            $table->unsignedInteger('total_quantity')->default(0)->comment('Tổng số lượng');
            $table->unsignedInteger('items_length')->default(0)->comment('Số loại sản phẩm');
            
            // Trạng thái đơn hàng
            $table->unsignedTinyInteger('status')->index()->comment('Trạng thái đơn hàng');
            $table->unsignedTinyInteger('sub_status')->nullable()->comment('Trạng thái phụ');
            $table->string('status_name', 50)->comment('Tên trạng thái');
            
            // Nguồn đơn hàng
            $table->tinyInteger('order_sources')->comment('Nguồn đơn hàng');
            $table->string('order_sources_name', 50)->comment('Tên nguồn đơn hàng');
            
            // Links và references
            $table->text('order_link')->nullable()->comment('Link đơn hàng');
            $table->text('link_confirm_order')->nullable()->comment('Link xác nhận đơn hàng');
            $table->string('conversation_id', 100)->nullable()->comment('ID cuộc trò chuyện');
            $table->string('post_id', 50)->nullable()->comment('ID bài viết');
            
            // Timestamps
            $table->timestamp('time_send_partner')->nullable()->comment('Thời gian gửi đối tác');
            $table->timestamp('pos_updated_at')->nullable()->comment('Thời gian cập nhật từ POS');
            $table->timestamps();
            
            // Indexes để tối ưu tìm kiếm
            $table->index(['order_id', 'customer_phone'], 'idx_order_phone'); // Composite index cho tìm kiếm chính
            $table->index(['status', 'created_at'], 'idx_status_created'); // Index cho filter theo trạng thái và thời gian
            $table->index(['page_id', 'created_at'], 'idx_page_created'); // Index cho filter theo trang
            $table->index('system_id', 'idx_system_id'); // Index cho filter theo hệ thống
        });
    }

    public function down()
    {
        Schema::dropIfExists('pos_orders');
    }
}