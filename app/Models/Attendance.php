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
        // Nếu đã có giá trị work_hours/work_minutes được lưu, sử dụng nó
        if ($this->work_hours || $this->work_minutes) {
            return sprintf('%02d:%02d', $this->work_hours, $this->work_minutes);
        }
        
        // Nếu chưa có, tính toán trực tiếp từ check_in_time và check_out_time
        if ($this->check_in_time && $this->check_out_time) {
            $checkIn = $this->check_in_time instanceof \Carbon\Carbon 
                ? $this->check_in_time 
                : \Carbon\Carbon::parse($this->check_in_time);
                
            $checkOut = $this->check_out_time instanceof \Carbon\Carbon 
                ? $this->check_out_time 
                : \Carbon\Carbon::parse($this->check_out_time);
                
            $diff = $checkOut->diff($checkIn);
            $hours = $diff->h + ($diff->days * 24);
            $minutes = $diff->i;
            
            return sprintf('%02d:%02d', $hours, $minutes);
        }
        
        // Nếu đang trong ca (chưa checkout)
        if ($this->check_in_time && !$this->check_out_time) {
            $checkIn = $this->check_in_time instanceof \Carbon\Carbon 
                ? $this->check_in_time 
                : \Carbon\Carbon::parse($this->check_in_time);
                
            $diff = now()->diff($checkIn);
            $hours = $diff->h + ($diff->days * 24);
            $minutes = $diff->i;
            
            return sprintf('%02d:%02d (đang làm)', $hours, $minutes);
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

    public function calculateWorkTime()
    {
        if ($this->check_in_time && $this->check_out_time) {
            $diff = $this->check_out_time->diff($this->check_in_time);
            $this->work_hours = $diff->h + ($diff->days * 24);
            $this->work_minutes = $diff->i;
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
