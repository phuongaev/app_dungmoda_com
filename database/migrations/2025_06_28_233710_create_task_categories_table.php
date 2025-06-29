<?php
// database/migrations/xxxx_create_task_categories_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class CreateTaskCategoriesTable extends Migration
{
    public function up()
    {
        Schema::create('task_categories', function (Blueprint $table) {
            $table->increments('id'); // Sử dụng increments thay vì id()
            $table->string('name');
            $table->string('color', 7)->default('#007bff');
            $table->string('icon')->nullable();
            $table->integer('sort_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });
    }

    public function down()
    {
        Schema::dropIfExists('task_categories');
    }
}