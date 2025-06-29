<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Encore\Admin\Auth\Database\Administrator;

class Attendance extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'check_in_time',
        'check_out_time', 
        'check_in_ip',
        'check_out_ip',
        'work_hours',
        'work_minutes',
        'work_date',
        'status',
        'notes'
    ];

    protected $casts = [
        'check_in_time' => 'datetime',
        'check_out_time' => 'datetime',
        'work_date' => 'date'
    ];

    public function user()
    {
        return $this->belongsTo(Administrator::class, 'user_id');
    }

    public function getTotalWorkTimeAttribute()
    {
        if ($this->work_hours || $this->work_minutes) {
            return sprintf('%02d:%02d', $this->work_hours, $this->work_minutes);
        }
        return '00:00';
    }

    // Attribute để hiển thị trạng thái bằng tiếng Việt
    public function getStatusLabelAttribute()
    {
        switch ($this->status) {
            case 'checked_in':
                return 'Đang làm việc';
            case 'checked_out':
                return 'Đã kết thúc ca';
            case 'incomplete':
                return 'Chưa hoàn thành';
            default:
                return 'Không xác định';
        }
    }

    // Tính toán thời gian làm việc
    public function calculateWorkTime()
    {
        if ($this->check_in_time && $this->check_out_time) {
            $checkIn = Carbon::parse($this->check_in_time);
            $checkOut = Carbon::parse($this->check_out_time);
            
            $totalMinutes = $checkOut->diffInMinutes($checkIn);
            
            $this->work_hours = intval($totalMinutes / 60);
            $this->work_minutes = $totalMinutes % 60;
            $this->status = 'checked_out';
            
            $this->save();
        }
    }

    // Scope để lấy attendance hôm nay của user
    public function scopeTodayByUser($query, $userId)
    {
        return $query->where('user_id', $userId)
                    ->where('work_date', today());
    }

    // Scope để lấy attendance tháng này
    public function scopeThisMonth($query)
    {
        return $query->whereMonth('work_date', now()->month)
                    ->whereYear('work_date', now()->year);
    }


    // Scope để lấy nhân viên đang online
    public function scopeOnlineToday($query)
    {
        return $query->where('work_date', today())
                    ->whereNotNull('check_in_time')
                    ->whereNull('check_out_time');
    }

    // Scope để lấy nhân viên đã kết thúc ca
    public function scopeCompletedToday($query)
    {
        return $query->where('work_date', today())
                    ->whereNotNull('check_in_time')
                    ->whereNotNull('check_out_time');
    }

}
