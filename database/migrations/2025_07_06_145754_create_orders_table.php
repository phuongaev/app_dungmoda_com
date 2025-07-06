<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            
            // Thông tin cơ bản đơn hàng
            $table->string('order_id')->unique()->index(); // id từ API như "MIX_211390061"
            $table->integer('system_id')->nullable()->index(); // system_id từ API
            $table->string('pos_global_id')->nullable()->index(); // pos_global_id từ API
            $table->string('account')->nullable()->index(); // account ID
            $table->integer('status')->index(); // trạng thái đơn hàng
            $table->integer('sub_status')->nullable();
            $table->string('status_name')->nullable();
            
            // Thông tin khách hàng
            $table->string('customer_id')->nullable()->index(); // customer.customer_id từ API
            $table->string('fb_id')->nullable()->index();
            $table->string('bill_phone_number')->nullable()->index(); // index cho tìm kiếm
            $table->string('bill_full_name')->nullable();
            
            // Thông tin fanpage
            $table->string('page_id')->nullable()->index();
            $table->string('page_name')->nullable();
            $table->string('post_id')->nullable();
            $table->string('conversation_id')->nullable();
            
            // Thông tin đơn hàng
            $table->string('order_link')->nullable();
            $table->string('link_confirm_order')->nullable();
            $table->string('order_sources')->nullable();
            $table->string('order_sources_name')->nullable();
            
            // Thông tin vận chuyển và giá
            $table->decimal('shipping_fee', 12, 2)->default(0);
            $table->decimal('cod', 12, 2)->default(0);
            $table->integer('total_quantity')->default(0);
            $table->integer('items_length')->default(0);
            
            // Thông tin xử lý
            $table->json('last_editor')->nullable(); // Lưu thông tin editor dạng JSON
            $table->timestamp('time_send_partner')->nullable();
            
            // Timestamps từ API
            $table->timestamp('inserted_at')->nullable();
            $table->timestamp('api_updated_at')->nullable(); // updated_at từ API
            
            $table->timestamps(); // created_at, updated_at của Laravel
            
            // Indexes cho tối ưu tìm kiếm
            $table->index(['status', 'created_at']);
            $table->index(['page_id', 'status']);
            $table->index('inserted_at');
            
            // QUAN TRỌNG: Indexes tối ưu cho tìm kiếm
            $table->index('bill_phone_number', 'idx_orders_phone'); // Tìm kiếm exact phone
            $table->fullText('bill_phone_number', 'ft_orders_phone'); // Tìm kiếm partial phone
            $table->fullText('order_id', 'ft_orders_order_id'); // Tìm kiếm partial order_id
            $table->fullText('bill_full_name', 'ft_orders_customer_name'); // Tìm kiếm tên khách hàng
            
            // Composite indexes cho queries phức tạp
            $table->index(['bill_phone_number', 'status'], 'idx_phone_status');
            $table->index(['page_id', 'bill_phone_number'], 'idx_page_phone');
            $table->index(['status', 'inserted_at', 'page_id'], 'idx_status_date_page');
            $table->index(['system_id', 'order_id'], 'idx_system_order'); // Composite cho system_id + order_id
            
            // Index cho sorting thường dùng
            $table->index(['inserted_at', 'id'], 'idx_date_id_desc'); // Order by date desc
            $table->index(['cod', 'status'], 'idx_cod_status'); // Filter by amount
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('orders');
    }
}