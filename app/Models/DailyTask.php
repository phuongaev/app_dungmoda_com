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
        'frequency' => 'array', // Thêm cast cho frequency
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
        return $this->isActiveOnDate($today);
    }

    // Check xem task có áp dụng cho ngày cụ thể không  
    public function isActiveOnDate($date)
    {
        // Check date range
        if ($this->start_date && $date->lt($this->start_date)) return false;
        if ($this->end_date && $date->gt($this->end_date)) return false;

        // Lấy frequency array
        $frequencies = $this->frequency ?? [];
        if (empty($frequencies)) return false;

        $dayOfWeek = strtolower($date->format('l'));

        // Check từng frequency trong array
        foreach ($frequencies as $freq) {
            if ($this->checkFrequencyMatch($freq, $date, $dayOfWeek)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check xem một frequency có match với ngày cụ thể không
     */
    private function checkFrequencyMatch($frequency, $date, $dayOfWeek)
    {
        switch ($frequency) {
            case 'daily':
                return true;
            case 'weekdays':
                return !$date->isWeekend();
            case 'weekends':
                return $date->isWeekend();
            case 'monday':
            case 'tuesday':
            case 'wednesday':
            case 'thursday':
            case 'friday':
            case 'saturday':
            case 'sunday':
                return $frequency === $dayOfWeek;
            default:
                return false;
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

    /**
     * Lấy label hiển thị cho frequency
     */
    public function getFrequencyLabelAttribute()
    {
        $frequencies = $this->frequency ?? [];
        if (empty($frequencies)) return '-';

        $labels = [
            'daily' => 'Hàng ngày',
            'weekdays' => 'Ngày làm việc',
            'weekends' => 'Cuối tuần',
            'monday' => 'Thứ 2',
            'tuesday' => 'Thứ 3',
            'wednesday' => 'Thứ 4',
            'thursday' => 'Thứ 5',
            'friday' => 'Thứ 6',
            'saturday' => 'Thứ 7',
            'sunday' => 'Chủ nhật'
        ];

        $displayLabels = [];
        foreach ($frequencies as $freq) {
            $displayLabels[] = $labels[$freq] ?? $freq;
        }

        return implode(', ', $displayLabels);
    }
}