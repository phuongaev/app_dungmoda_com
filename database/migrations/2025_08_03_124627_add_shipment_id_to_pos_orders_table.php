<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddShipmentIdToPosOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pos_orders', function (Blueprint $table) {
            // Thêm cột shipment_id sau cột order_id
            $table->string('shipment_id', 255)->nullable()->after('order_id')->comment('ID vận đơn giao hàng');
            
            // Thêm index cho performance khi filter/search theo shipment_id
            $table->index('shipment_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pos_orders', function (Blueprint $table) {
            // Xóa index trước khi xóa cột
            $table->dropIndex(['shipment_id']);
            $table->dropColumn('shipment_id');
        });
    }
}