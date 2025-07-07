<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddIndexesForInsertedAtToPosOrders extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pos_orders', function (Blueprint $table) {
            // 1. Index đơn cho inserted_at (để sort và filter theo thời gian)
            $table->index('inserted_at', 'idx_inserted_at');
            
            // 2. Composite index cho status + inserted_at (filter trạng thái theo thời gian)
            $table->index(['status', 'inserted_at'], 'idx_status_inserted_at');
            
            // 3. Composite index cho customer_phone + inserted_at (tìm kiếm khách hàng theo thời gian)
            $table->index(['customer_phone', 'inserted_at'], 'idx_phone_inserted_at');
            
            // 5. Composite index 3 cột cho filter phức tạp (status + page_id + inserted_at)
            $table->index(['status', 'page_id', 'inserted_at'], 'idx_status_page_inserted_at');
            
            // 6. Composite index cho order_id + inserted_at (tìm kiếm mã đơn theo thời gian)
            $table->index(['order_id', 'inserted_at'], 'idx_order_id_inserted_at');
        });

        // 7. Tạo thêm index bằng raw SQL cho performance tốt hơn
        DB::statement('CREATE INDEX idx_inserted_at_desc ON pos_orders (inserted_at DESC, id DESC)');
        
        // 8. Composite index cho COD range + thời gian
        DB::statement('CREATE INDEX idx_cod_inserted_at ON pos_orders (cod, inserted_at DESC)');
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pos_orders', function (Blueprint $table) {
            // Drop các index được tạo bằng Schema
            $table->dropIndex('idx_inserted_at');
            $table->dropIndex('idx_status_inserted_at');
            $table->dropIndex('idx_phone_inserted_at');
            $table->dropIndex('idx_status_page_inserted_at');
            $table->dropIndex('idx_order_id_inserted_at');
        });

        // Drop các index được tạo bằng raw SQL
        DB::statement('DROP INDEX idx_inserted_at_desc ON pos_orders');
        DB::statement('DROP INDEX idx_cod_inserted_at ON pos_orders');
    }
}