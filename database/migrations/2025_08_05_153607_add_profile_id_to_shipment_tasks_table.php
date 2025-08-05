<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddProfileIdToShipmentTasksTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('shipment_tasks', function (Blueprint $table) {
            // Thêm cột profile_id sau cột shipment_id
            $table->string('profile_id', 255)->after('shipment_id')->comment('ID tài khoản Zalo để chạy task');
            
            // Tạo index cho profile_id để tối ưu query
            $table->index('profile_id', 'idx_profile_id');
            
            // Composite index cho profile_id + status (để filter task theo tài khoản và trạng thái)
            $table->index(['profile_id', 'status'], 'idx_profile_status');
            
            // Composite index cho profile_id + created_at (để sort task theo tài khoản và thời gian)
            $table->index(['profile_id', 'created_at'], 'idx_profile_created_at');
            
            // Foreign key constraint với customers.profile_id
            $table->foreign('profile_id')
                  ->references('profile_id')
                  ->on('customers')
                  ->onDelete('cascade')
                  ->name('fk_shipment_tasks_profile_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('shipment_tasks', function (Blueprint $table) {
            // Xóa foreign key constraint trước
            $table->dropForeign('fk_shipment_tasks_profile_id');
            
            // Xóa các index
            $table->dropIndex('idx_profile_id');
            $table->dropIndex('idx_profile_status');
            $table->dropIndex('idx_profile_created_at');
            
            // Xóa cột profile_id
            $table->dropColumn('profile_id');
        });
    }
}