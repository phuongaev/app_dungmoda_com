<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddDatasetStatusToPosOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pos_orders', function (Blueprint $table) {
            $table->integer('dataset_status', false, true)->length(5)->nullable()->after('sub_status');
            
            // Thêm index cho performance khi filter
            $table->index('dataset_status');
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
            $table->dropIndex(['dataset_status']);
            $table->dropColumn('dataset_status');
        });
    }
}