<?php
// database/migrations/xxxx_create_user_task_completions_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateUserTaskCompletionsTable extends Migration
{
    public function up()
    {
        Schema::create('user_task_completions', function (Blueprint $table) {
            $table->increments('id'); // Sử dụng increments
            
            // Foreign keys với unsignedInteger
            $table->unsignedInteger('daily_task_id');
            $table->foreign('daily_task_id')->references('id')->on('daily_tasks')->onDelete('cascade');
            
            $table->unsignedInteger('user_id');
            $table->foreign('user_id')->references('id')->on('admin_users')->onDelete('cascade');
            
            $table->date('completion_date');
            $table->time('completed_at_time')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['completed', 'skipped', 'failed'])->default('completed');
            $table->timestamps();
            
            // Unique constraint
            $table->unique(['daily_task_id', 'user_id', 'completion_date'], 'unique_daily_task_completion');
        });
    }

    public function down()
    {
        Schema::dropIfExists('user_task_completions');
    }
}