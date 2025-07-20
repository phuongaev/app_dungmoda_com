<?php
// database/migrations/2025_07_16_200000_add_review_status_to_user_task_completions.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

class AddReviewStatusToUserTaskCompletions extends Migration
{
    public function up()
    {
        Schema::table('user_task_completions', function (Blueprint $table) {
            // Chỉ thêm 1 cột boolean đơn giản
            $table->boolean('review_status')->default(0)->after('status')
                ->comment('0 = không cần review, 1 = cần review lại');
        });
    }

    public function down()
    {
        Schema::table('user_task_completions', function (Blueprint $table) {
            $table->dropColumn('review_status');
        });
    }
}