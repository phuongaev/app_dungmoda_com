<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddThreadIdToAdminUsersTable extends Migration
{
    public function up()
    {
        Schema::table('admin_users', function (Blueprint $table) {
            // Thêm cột thread_id sau cột is_active
            $table->string('thread_id', 100)->nullable()->after('is_active');
        });
    }

    public function down()
    {
        Schema::table('admin_users', function (Blueprint $table) {
            $table->dropColumn('thread_id');
        });
    }
}