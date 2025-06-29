<?php
// app/Models/DailyTask.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class DailyTask extends Model
{
    protected $fillable = [
        'title', 'description', 'category_id', 'priority', 'suggested_time',
        'estimated_minutes', 'assigned_roles', 'assigned_users', 'frequency',
        'start_date', 'end_date', 'is_required', 'is_active', 'sort_order', 'created_by'
    ];

    protected $casts = [
        'assigned_roles' => 'array',
        'assigned_users' => 'array',
        'start_date' => 'date',
        'end_date' => 'date',
        'suggested_time' => 'datetime:H:i',
        'is_required' => 'boolean',
        'is_active' => 'boolean'
    ];

    public function category()
    {
        return $this->belongsTo(TaskCategory::class, 'category_id');
    }

    public function creator()
    {
        return $this->belongsTo(\Encore\Admin\Auth\Database\Administrator::class, 'created_by');
    }

    public function completions()
    {
        return $this->hasMany(UserTaskCompletion::class);
    }

    public function todayCompletion($userId)
    {
        return $this->completions()
            ->where('user_id', $userId)
            ->where('completion_date', today())
            ->first();
    }

    // Check xem task có áp dụng cho ngày hôm nay không
    public function isActiveToday()
    {
        $today = Carbon::today();
        $dayOfWeek = strtolower($today->format('l'));

        // Check date range
        if ($this->start_date && $today->lt($this->start_date)) return false;
        if ($this->end_date && $today->gt($this->end_date)) return false;

        // Check frequency
        switch ($this->frequency) {
            case 'daily':
                return true;
            case 'weekdays':
                return !$today->isWeekend();
            case 'weekends':
                return $today->isWeekend();
            default:
                return $this->frequency === $dayOfWeek;
        }
    }

    // Check user có được assign task này không
    public function isAssignedToUser($userId, $userRoles = [])
    {
        // Check assigned users
        if ($this->assigned_users && in_array($userId, $this->assigned_users)) {
            return true;
        }

        // Check assigned roles
        if ($this->assigned_roles && $userRoles) {
            return !empty(array_intersect($this->assigned_roles, $userRoles));
        }

        // Nếu không assign cụ thể thì áp dụng cho tất cả
        return empty($this->assigned_users) && empty($this->assigned_roles);
    }

    public function getPriorityLabelAttribute()
    {
        $labels = [
            'low' => 'Thấp',
            'medium' => 'Trung bình', 
            'high' => 'Cao',
            'urgent' => 'Khẩn cấp'
        ];
        return $labels[$this->priority] ?? 'Trung bình';
    }

    public function getPriorityColorAttribute()
    {
        $colors = [
            'low' => '#28a745',
            'medium' => '#ffc107',
            'high' => '#fd7e14', 
            'urgent' => '#dc3545'
        ];
        return $colors[$this->priority] ?? '#ffc107';
    }
}