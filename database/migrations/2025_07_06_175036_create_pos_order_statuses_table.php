<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class CreatePosOrderStatusesTable extends Migration
{
    public function up()
    {
        Schema::create('pos_order_statuses', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('status_code')->unique()->comment('Mã trạng thái');
            $table->string('status_name', 100)->comment('Tên trạng thái');
            $table->string('status_color', 20)->default('default')->comment('Màu hiển thị');
            $table->string('description', 255)->nullable()->comment('Mô tả');
            $table->boolean('is_active')->default(true)->comment('Trạng thái hoạt động');
            $table->unsignedTinyInteger('sort_order')->default(0)->comment('Thứ tự sắp xếp');
            $table->timestamps();
            
            $table->index('is_active');
            $table->index('sort_order');
        });

        // Insert dữ liệu trạng thái
        DB::table('pos_order_statuses')->insert([
            ['status_code' => 0, 'status_name' => 'Mới', 'status_color' => 'default', 'sort_order' => 1, 'created_at' => now(), 'updated_at' => now()],
            ['status_code' => 11, 'status_name' => 'Chờ hàng', 'status_color' => 'warning', 'sort_order' => 2, 'created_at' => now(), 'updated_at' => now()],
            ['status_code' => 20, 'status_name' => 'Đã đặt hàng', 'status_color' => 'info', 'sort_order' => 3, 'created_at' => now(), 'updated_at' => now()],
            ['status_code' => 1, 'status_name' => 'Đã xác nhận', 'status_color' => 'primary', 'sort_order' => 4, 'created_at' => now(), 'updated_at' => now()],
            ['status_code' => 12, 'status_name' => 'Chờ in', 'status_color' => 'warning', 'sort_order' => 5, 'created_at' => now(), 'updated_at' => now()],
            ['status_code' => 13, 'status_name' => 'Đã in', 'status_color' => 'info', 'sort_order' => 6, 'created_at' => now(), 'updated_at' => now()],
            ['status_code' => 8, 'status_name' => 'Đang đóng hàng', 'status_color' => 'primary', 'sort_order' => 7, 'created_at' => now(), 'updated_at' => now()],
            ['status_code' => 9, 'status_name' => 'Chờ chuyển hàng', 'status_color' => 'warning', 'sort_order' => 8, 'created_at' => now(), 'updated_at' => now()],
            ['status_code' => 2, 'status_name' => 'Đã gửi hàng', 'status_color' => 'info', 'sort_order' => 9, 'created_at' => now(), 'updated_at' => now()],
            ['status_code' => 3, 'status_name' => 'Đã nhận', 'status_color' => 'success', 'sort_order' => 10, 'created_at' => now(), 'updated_at' => now()],
            ['status_code' => 16, 'status_name' => 'Đã thu tiền', 'status_color' => 'success', 'sort_order' => 11, 'created_at' => now(), 'updated_at' => now()],
            ['status_code' => 4, 'status_name' => 'Đang hoàn', 'status_color' => 'danger', 'sort_order' => 12, 'created_at' => now(), 'updated_at' => now()],
            ['status_code' => 15, 'status_name' => 'Hoàn một phần', 'status_color' => 'warning', 'sort_order' => 13, 'created_at' => now(), 'updated_at' => now()],
            ['status_code' => 5, 'status_name' => 'Đã hoàn', 'status_color' => 'danger', 'sort_order' => 14, 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down()
    {
        Schema::dropIfExists('pos_order_statuses');
    }
}