<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddAddressFieldsToPosOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pos_orders', function (Blueprint $table) {
            // Thêm các field địa chỉ sau cột customer_fb_id
            $table->text('customer_addresses')->nullable()->after('customer_fb_id')->comment('Địa chỉ khách hàng');
            $table->string('province_name', 100)->nullable()->after('customer_addresses')->comment('Tên tỉnh/thành phố');
            $table->string('province_id', 20)->nullable()->after('province_name')->comment('ID tỉnh/thành phố');
            $table->string('district_name', 100)->nullable()->after('province_id')->comment('Tên quận/huyện');
            $table->string('district_id', 20)->nullable()->after('district_name')->comment('ID quận/huyện');
            $table->string('commune_name', 100)->nullable()->after('district_id')->comment('Tên phường/xã');
            $table->string('commune_id', 20)->nullable()->after('commune_name')->comment('ID phường/xã');
            
            // Thêm index cho các field địa chỉ để tối ưu tìm kiếm
            $table->index('province_id', 'idx_province_id');
            $table->index('district_id', 'idx_district_id');
            $table->index('commune_id', 'idx_commune_id');
            $table->index(['province_id', 'district_id'], 'idx_province_district');
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
            // Xóa các index trước khi xóa cột
            $table->dropIndex('idx_province_id');
            $table->dropIndex('idx_district_id'); 
            $table->dropIndex('idx_commune_id');
            $table->dropIndex('idx_province_district');
            
            // Xóa các cột
            $table->dropColumn([
                'customer_addresses',
                'province_name',
                'province_id',
                'district_name',
                'district_id',
                'commune_name',
                'commune_id'
            ]);
        });
    }
}