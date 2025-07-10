<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AlterEveningShiftsTableRemoveUniqueDateConstraint extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('evening_shifts', function (Blueprint $table) {
            // Gỡ bỏ ràng buộc unique trên cột shift_date
            // Tên của ràng buộc thường là 'tên_bảng_tên_cột_unique'
            $table->dropUnique('evening_shifts_shift_date_unique');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('evening_shifts', function (Blueprint $table) {
            // Thêm lại ràng buộc nếu cần rollback
            $table->unique('shift_date');
        });
    }
}