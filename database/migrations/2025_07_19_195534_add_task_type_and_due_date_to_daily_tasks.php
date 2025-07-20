<?php
// database/migrations/2025_07_16_100000_add_task_type_and_due_date_to_daily_tasks.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddTaskTypeAndDueDateToDailyTasks extends Migration
{
    public function up()
    {
        Schema::table('daily_tasks', function (Blueprint $table) {
            // Chỉ thêm cột task_type, không cần due_date
            $table->enum('task_type', ['recurring', 'one_time'])->default('recurring')->after('frequency');
        });
        
        // Cập nhật dữ liệu cũ - tất cả task hiện tại đều là recurring
        DB::table('daily_tasks')->update(['task_type' => 'recurring']);
    }

    public function down()
    {
        Schema::table('daily_tasks', function (Blueprint $table) {
            $table->dropColumn(['task_type']);
        });
    }
}