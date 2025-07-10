<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateEveningShiftsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('evening_shifts', function (Blueprint $table) {
            $table->increments('id');
            $table->integer('admin_user_id')->unsigned();
            $table->date('shift_date');
            $table->timestamps();

            // Thêm khóa ngoại để liên kết với bảng admin_users
            $table->foreign('admin_user_id')->references('id')->on('admin_users')->onDelete('cascade');
            
            // Đảm bảo mỗi ngày chỉ có một nhân viên được phân công
            // Nếu sau này anh muốn 1 ngày có nhiều nhân viên, chỉ cần xóa dòng unique này đi
            $table->unique(['admin_user_id', 'shift_date']);
            $table->unique('shift_date'); // Đảm bảo 1 ngày chỉ có 1 ca
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('evening_shifts');
    }
}