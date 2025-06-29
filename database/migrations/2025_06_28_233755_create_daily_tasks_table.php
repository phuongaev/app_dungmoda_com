<?php
// database/migrations/xxxx_create_daily_tasks_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateDailyTasksTable extends Migration
{
    public function up()
    {
        Schema::create('daily_tasks', function (Blueprint $table) {
            $table->increments('id'); // Sử dụng increments
            $table->string('title');
            $table->text('description')->nullable();
            
            // Foreign key với unsignedInteger
            $table->unsignedInteger('category_id')->nullable();
            $table->foreign('category_id')->references('id')->on('task_categories')->onDelete('set null');
            
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])->default('medium');
            $table->time('suggested_time')->nullable();
            $table->integer('estimated_minutes')->nullable();
            $table->json('assigned_roles')->nullable();
            $table->json('assigned_users')->nullable();
            $table->enum('frequency', [
                'daily', 'weekdays', 'weekends', 
                'monday', 'tuesday', 'wednesday', 'thursday', 
                'friday', 'saturday', 'sunday'
            ])->default('daily');
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->boolean('is_required')->default(true);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            
            // Foreign key cho admin_users
            $table->unsignedInteger('created_by');
            $table->foreign('created_by')->references('id')->on('admin_users')->onDelete('cascade');
            
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('daily_tasks');
    }
}