<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class RemoveUpdatedAtFromWorkflowHistoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('pos_order_workflow_histories', function (Blueprint $table) {
            $table->dropColumn('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('pos_order_workflow_histories', function (Blueprint $table) {
            $table->timestamp('updated_at')->nullable();
        });
    }
}