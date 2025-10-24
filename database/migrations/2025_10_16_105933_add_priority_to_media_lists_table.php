<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddPriorityToMediaListsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('media_lists', function (Blueprint $table) {
            // Thêm cột priority sau cột status
            $table->boolean('priority')->default(0)->after('status')->comment('0 = không ưu tiên, 1 = ưu tiên');
            
            // Thêm index để tối ưu query khi filter theo priority
            $table->index('priority');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('media_lists', function (Blueprint $table) {
            $table->dropIndex(['priority']);
            $table->dropColumn('priority');
        });
    }
}