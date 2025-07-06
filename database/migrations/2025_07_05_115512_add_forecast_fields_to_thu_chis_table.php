<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddForecastFieldsToThuChisTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::table('cashs', function (Blueprint $table) {
            $table->boolean('is_recurring')->default(false);
            $table->string('recurring_period')->nullable(); // daily, weekly, monthly, yearly
            $table->date('next_recurring_date')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::table('cashs', function (Blueprint $table) {
            //
        });
    }
}
