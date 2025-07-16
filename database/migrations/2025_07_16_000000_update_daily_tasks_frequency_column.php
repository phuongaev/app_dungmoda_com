<?php
// database/migrations/2025_07_16_000000_update_daily_tasks_frequency_column.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class UpdateDailyTasksFrequencyColumn extends Migration
{
    public function up()
    {
        // Backup dữ liệu cũ để convert
        $oldTasks = DB::table('daily_tasks')->get();
        
        // Thay đổi cột frequency từ enum sang text
        Schema::table('daily_tasks', function (Blueprint $table) {
            $table->text('frequency')->change();
        });
        
        // Convert dữ liệu cũ sang format mới
        foreach ($oldTasks as $task) {
            $newFrequency = $this->convertOldFrequency($task->frequency);
            DB::table('daily_tasks')
                ->where('id', $task->id)
                ->update(['frequency' => $newFrequency]);
        }
    }

    public function down()
    {
        // Convert lại về enum format (chỉ giữ giá trị đầu tiên nếu có nhiều)
        $tasks = DB::table('daily_tasks')->get();
        
        Schema::table('daily_tasks', function (Blueprint $table) {
            $table->enum('frequency', [
                'daily', 'weekdays', 'weekends', 
                'monday', 'tuesday', 'wednesday', 'thursday', 
                'friday', 'saturday', 'sunday'
            ])->default('daily')->change();
        });
        
        // Convert dữ liệu về format cũ
        foreach ($tasks as $task) {
            $frequency = json_decode($task->frequency, true);
            if (is_array($frequency) && !empty($frequency)) {
                $oldFrequency = $frequency[0]; // Lấy giá trị đầu tiên
            } else {
                $oldFrequency = $task->frequency; // Giữ nguyên nếu không phải array
            }
            
            DB::table('daily_tasks')
                ->where('id', $task->id)
                ->update(['frequency' => $oldFrequency]);
        }
    }
    
    /**
     * Convert frequency cũ sang format mới
     */
    private function convertOldFrequency($oldFrequency)
    {
        // Nếu là giá trị single, convert thành array
        return json_encode([$oldFrequency]);
    }
}