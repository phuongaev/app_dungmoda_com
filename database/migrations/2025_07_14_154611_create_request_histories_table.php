<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateRequestHistoriesTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('request_histories', function (Blueprint $table) {
            $table->id();
            $table->enum('request_type', ['leave', 'swap'])->comment('Loại đơn: nghỉ hoặc hoán đổi');
            $table->unsignedBigInteger('request_id')->comment('ID của đơn');
            $table->enum('action', ['submitted', 'approved', 'rejected', 'cancelled'])->comment('Hành động');
            $table->unsignedInteger('admin_user_id')->comment('Người thực hiện hành động');
            $table->text('notes')->nullable()->comment('Ghi chú');
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('admin_user_id')->references('id')->on('admin_users')->onDelete('cascade');
            
            // Indexes
            $table->index(['request_type', 'request_id']);
            $table->index('action');
            $table->index('admin_user_id');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('request_histories');
    }
}