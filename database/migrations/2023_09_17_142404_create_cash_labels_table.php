<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateCashLabelsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('cash_labels', function (Blueprint $table) {
            $table->unsignedBigInteger('cash_id');
            $table->unsignedBigInteger('label_id');

            $table->foreign('cash_id')
                ->references('id')
                ->on('cashs')
                ->onDelete('cascade');
            $table->foreign('label_id')
                ->references('id')
                ->on('labels')
                ->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('cash_labels');
    }
}
