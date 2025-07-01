<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddIsActiveToAdminUsersTable extends Migration
{
    public function up()
    {
        Schema::table('admin_users', function (Blueprint $table) {
            // Thêm cột is_active, dạng boolean, mặc định là true (1)
            // Đặt sau cột 'name' cho dễ nhìn
            $table->boolean('is_active')->default(true)->after('remember_token');
        });
    }

    public function down()
    {
        Schema::table('admin_users', function (Blueprint $table) {
            $table->dropColumn('is_active');
        });
    }
}