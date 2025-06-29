<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateImportOrdersTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('import_orders', function (Blueprint $table) {
            $table->id();
            $table->string('order_code')->unique();
            $table->string('pancake_id');
            $table->integer('quantity')->default(0);
            $table->integer('quantity_bill')->default(0);
            $table->string('supplier_code');
            $table->text('notes')->nullable();
            $table->enum('import_status', ['pending', 'processing', 'in_transit', 'completed', 'cancelled'])->default('pending');
            $table->timestamps();
            
            $table->index('order_code');
            $table->index('supplier_code');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('import_orders');
    }
}