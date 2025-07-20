<?php
// app/Models/UserTaskCompletion.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserTaskCompletion extends Model
{
    protected $fillable = [
        'daily_task_id', 'user_id', 'completion_date', 
        'completed_at_time', 'notes', 'status', 'review_status'
    ];

    protected $casts = [
        'completion_date' => 'date',
        'completed_at_time' => 'datetime:H:i:s',
        'review_status' => 'boolean'
    ];

    public function dailyTask()
    {
        return $this->belongsTo(DailyTask::class);
    }

    public function user()
    {
        return $this->belongsTo(\Encore\Admin\Auth\Database\Administrator::class, 'user_id');
    }

    public function getStatusLabelAttribute()
    {
        $labels = [
            'completed' => 'Hoàn thành',
            'skipped' => 'Bỏ qua',
            'failed' => 'Thất bại'
        ];
        return $labels[$this->status] ?? 'Hoàn thành';
    }

    public function getReviewStatusBadgeAttribute()
    {
        if ($this->review_status) {
            return '<span class="label label-warning">Cần kiểm tra lại</span>';
        }
        return '<span class="label label-success">OK</span>';
    }

    /**
     * Check xem completion có phải của one-time task không
     */
    public function isOneTimeTask()
    {
        return $this->dailyTask && $this->dailyTask->task_type === 'one_time';
    }
}