<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddLastRunAtToZaloPagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('zalo_pages', function (Blueprint $table) {
            $table->timestamp('last_run_at')->nullable()->comment('Lần chạy cuối cùng')->after('pc_user_name');
            $table->index('last_run_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('zalo_pages', function (Blueprint $table) {
            $table->dropIndex(['last_run_at']);
            $table->dropColumn('last_run_at');
        });
    }
}