<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateSyncJobsTable extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up()
    {
        Schema::create('sync_jobs', function (Blueprint $table) {
            $table->id();
            $table->string('job_type')->default('orders_sync'); // Loại sync job
            $table->string('status')->default('pending'); // pending, running, completed, failed
            $table->integer('current_page')->default(1); // Trang hiện tại đang sync
            $table->integer('total_pages')->nullable(); // Tổng số trang (từ API response)
            $table->integer('total_records')->nullable(); // Tổng số records (từ API response)
            $table->integer('synced_records')->default(0); // Số records đã sync
            $table->json('api_params')->nullable(); // Lưu parameters API
            $table->text('error_message')->nullable(); // Lưu lỗi nếu có
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            
            $table->index(['job_type', 'status']);
            $table->index('current_page');
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down()
    {
        Schema::dropIfExists('sync_jobs');
    }
}