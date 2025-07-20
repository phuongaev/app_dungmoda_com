<?php
// database/migrations/2025_07_20_add_in_process_status_to_user_task_completions.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class AddInProcessStatusToUserTaskCompletions extends Migration
{
    public function up()
    {
        // Thay đổi enum để thêm 'in_process'
        DB::statement("ALTER TABLE user_task_completions MODIFY COLUMN status ENUM('completed', 'skipped', 'failed', 'in_process') DEFAULT 'completed'");
    }

    public function down()
    {
        // Revert lại enum cũ (xóa in_process)
        DB::statement("ALTER TABLE user_task_completions MODIFY COLUMN status ENUM('completed', 'skipped', 'failed') DEFAULT 'completed'");
    }
}