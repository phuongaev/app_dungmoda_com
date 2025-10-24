<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateZaloPagesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('zalo_pages', function (Blueprint $table) {
            $table->id();
            $table->string('phone_number', 20)->unique()->comment('Số điện thoại Zalo');
            $table->string('global_id', 255)->nullable()->comment('Global ID');
            $table->date('sdob')->nullable()->comment('Ngày sinh');
            $table->string('zalo_name', 255)->nullable()->comment('Tên Zalo');
            $table->text('avatar_url')->nullable()->comment('Link avatar');
            $table->string('pc_page_id', 255)->nullable()->comment('PC Page ID');
            $table->string('pc_user_name', 255)->nullable()->comment('PC Username');
            $table->timestamps();
            $table->softDeletes();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('zalo_pages');
    }
}