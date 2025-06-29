<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreatePackagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('packages', function (Blueprint $table) {
            $table->id();
            $table->string('package_code');
            $table->enum('shipping_partner', ['atan', 'nga', 'fe'])->default('atan');
            $table->text('notes')->nullable();
            $table->enum('package_status', ['pending', 'in_transit', 'delivered', 'cancelled'])->default('pending');
            $table->timestamps();
            
            $table->index('package_code');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('packages');
    }
}
