<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddBotcakeTokenToFanPagesTable extends Migration
{
    public function up()
    {
        Schema::table('fan_pages', function (Blueprint $table) {
            $table->text('botcake_token')->nullable()->after('pancake_token');
        });
    }

    public function down()
    {
        Schema::table('fan_pages', function (Blueprint $table) {
            $table->dropColumn('botcake_token');
        });
    }
}
