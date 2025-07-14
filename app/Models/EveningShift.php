<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Encore\Admin\Auth\Database\Administrator;
use Carbon\Carbon;

class EveningShift extends Model
{
    protected $fillable = [
        'admin_user_id',
        'shift_date'
    ];

    protected $dates = [
        'shift_date'
    ];

    protected $casts = [
        'shift_date' => 'date'
    ];

    /**
     * Relationship với user
     */
    public function user()
    {
        return $this->belongsTo(Administrator::class, 'admin_user_id');
    }

    /**
     * Lấy tên hiển thị cho shift date
     */
    public function getShiftDateFormattedAttribute()
    {
        return $this->shift_date ? $this->shift_date->format('d/m/Y') : '';
    }

    /**
     * Kiểm tra xem ngày này có ai đang xin nghỉ không
     */
    public function getOnLeaveUsersAttribute()
    {
        if (!$this->shift_date) {
            return collect([]);
        }

        return \App\Models\LeaveRequest::with('employee')
            ->where('status', \App\Models\LeaveRequest::STATUS_APPROVED)
            ->where('start_date', '<=', $this->shift_date)
            ->where('end_date', '>=', $this->shift_date)
            ->get()
            ->pluck('employee')
            ->filter();
    }

    /**
     * Kiểm tra xem user được assign cho ca này có đang nghỉ không
     */
    public function getIsUserOnLeaveAttribute()
    {
        if (!$this->admin_user_id || !$this->shift_date) {
            return false;
        }

        return \App\Models\LeaveRequest::where('admin_user_id', $this->admin_user_id)
            ->where('status', \App\Models\LeaveRequest::STATUS_APPROVED)
            ->where('start_date', '<=', $this->shift_date)
            ->where('end_date', '>=', $this->shift_date)
            ->exists();
    }

    /**
     * Lấy thông tin hiển thị cho calendar
     */
    public function getCalendarDisplayAttribute()
    {
        $display = [
            'user_name' => $this->user ? $this->user->name : 'Chưa phân công',
            'user_id' => $this->admin_user_id,
            'is_on_leave' => $this->is_user_on_leave,
            'on_leave_users' => $this->on_leave_users,
            'date' => $this->shift_date ? $this->shift_date->format('Y-m-d') : null,
            'display_date' => $this->shift_date_formatted
        ];

        // Thêm thông tin về việc hoán đổi ca (nếu có)
        $swapInfo = $this->getSwapInfo();
        if ($swapInfo) {
            $display['swap_info'] = $swapInfo;
            $display['is_swapped'] = true;
        } else {
            $display['is_swapped'] = false;
        }

        return $display;
    }

    /**
     * Lấy thông tin hoán đổi ca nếu có
     */
    public function getSwapInfo()
    {
        if (!$this->shift_date) {
            return null;
        }

        // Tìm đơn hoán đổi ca đã được duyệt liên quan đến ngày này
        $swapRequest = \App\Models\ShiftSwapRequest::with(['requester', 'targetUser'])
            ->where('status', \App\Models\ShiftSwapRequest::STATUS_APPROVED)
            ->where(function ($query) {
                $query->where('original_requester_shift_date', $this->shift_date)
                      ->orWhere('original_target_shift_date', $this->shift_date);
            })
            ->first();

        if (!$swapRequest) {
            return null;
        }

        // Xác định ai đã được hoán đổi vào ca này
        if ($swapRequest->original_requester_shift_date->equalTo($this->shift_date)) {
            // Đây là ca gốc của requester, nhưng giờ target user đang trực
            return [
                'original_user' => $swapRequest->requester->name,
                'current_user' => $swapRequest->targetUser->name,
                'swap_type' => 'received', // target user nhận ca này
                'swap_id' => $swapRequest->id
            ];
        } elseif ($swapRequest->original_target_shift_date->equalTo($this->shift_date)) {
            // Đây là ca gốc của target user, nhưng giờ requester đang trực
            return [
                'original_user' => $swapRequest->targetUser->name,
                'current_user' => $swapRequest->requester->name,
                'swap_type' => 'given', // requester nhận ca này
                'swap_id' => $swapRequest->id
            ];
        }

        return null;
    }

    /**
     * Scope để lấy ca trực trong khoảng thời gian
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->whereBetween('shift_date', [$startDate, $endDate]);
    }

    /**
     * Scope để lấy ca trực của user cụ thể
     */
    public function scopeOfUser($query, $userId)
    {
        return $query->where('admin_user_id', $userId);
    }

    /**
     * Scope để lấy ca trực từ hôm nay trở đi
     */
    public function scopeUpcoming($query)
    {
        return $query->where('shift_date', '>=', Carbon::today());
    }

    /**
     * Scope to get shifts for a specific user from today onwards
     */
    public function scopeForUserUpcoming($query, $userId)
    {
        return $query->where('admin_user_id', $userId)
                     ->where('shift_date', '>=', Carbon::today())
                     ->orderBy('shift_date');
    }

    /**
     * Get shift options for select dropdown
     */
    public static function getShiftOptionsForUser($userId)
    {
        return static::forUserUpcoming($userId)
            ->get()
            ->mapWithKeys(function ($shift) {
                return [$shift->id => $shift->shift_date->format('d/m/Y')];
            });
    }

    /**
     * Check if this shift can be swapped
     */
    public function canBeSwapped()
    {
        // Không thể hoán đổi ca trong quá khứ
        if ($this->shift_date->isPast()) {
            return false;
        }
        
        // Không thể hoán đổi nếu đã có đơn hoán đổi pending
        $existingSwap = \App\Models\ShiftSwapRequest::where('status', \App\Models\ShiftSwapRequest::STATUS_PENDING)
            ->where(function ($query) {
                $query->where('requester_shift_id', $this->id)
                      ->orWhere('target_shift_id', $this->id);
            })
            ->exists();
            
        return !$existingSwap;
    }

    /**
     * Get conflicts for this shift
     */
    public function getConflicts()
    {
        $conflicts = [];
        
        // Check for leave requests
        if ($this->is_user_on_leave) {
            $conflicts[] = 'User đang trong thời gian nghỉ phép';
        }
        
        // Check for pending swap requests
        $pendingSwap = \App\Models\ShiftSwapRequest::where('status', \App\Models\ShiftSwapRequest::STATUS_PENDING)
            ->where(function ($query) {
                $query->where('requester_shift_id', $this->id)
                      ->orWhere('target_shift_id', $this->id);
            })
            ->first();
            
        if ($pendingSwap) {
            $conflicts[] = 'Đang có đơn hoán đổi chờ duyệt';
        }
        
        return $conflicts;
    }

    /**
     * Lấy danh sách ca trực với thông tin đầy đủ cho calendar
     */
    public static function getCalendarData($startDate, $endDate)
    {
        return static::with(['user'])
            ->inDateRange($startDate, $endDate)
            ->orderBy('shift_date')
            ->get()
            ->map(function ($shift) {
                return [
                    'id' => $shift->id,
                    'title' => $shift->calendar_display['user_name'],
                    'start' => $shift->calendar_display['date'],
                    'allDay' => true,
                    'user_id' => $shift->calendar_display['user_id'],
                    'is_on_leave' => $shift->calendar_display['is_on_leave'],
                    'is_swapped' => $shift->calendar_display['is_swapped'],
                    'swap_info' => $shift->calendar_display['swap_info'] ?? null,
                    'on_leave_users' => $shift->calendar_display['on_leave_users']->map(function ($user) {
                        return [
                            'id' => $user->id,
                            'name' => $user->name
                        ];
                    }),
                    'className' => static::getCalendarClassName($shift->calendar_display),
                    'backgroundColor' => static::getCalendarBackgroundColor($shift->calendar_display),
                    'borderColor' => static::getCalendarBorderColor($shift->calendar_display),
                ];
            });
    }

    /**
     * Lấy class name cho calendar event
     */
    private static function getCalendarClassName($display)
    {
        $classes = ['shift-event'];
        
        if ($display['is_on_leave']) {
            $classes[] = 'on-leave';
        }
        
        if ($display['is_swapped']) {
            $classes[] = 'swapped';
        }
        
        return implode(' ', $classes);
    }

    /**
     * Lấy màu nền cho calendar event
     */
    private static function getCalendarBackgroundColor($display)
    {
        if ($display['is_on_leave']) {
            return '#d9534f'; // Red for on leave
        }
        
        if ($display['is_swapped']) {
            return '#f0ad4e'; // Orange for swapped
        }
        
        return '#5cb85c'; // Green for normal
    }

    /**
     * Lấy màu viền cho calendar event
     */
    private static function getCalendarBorderColor($display)
    {
        if ($display['is_on_leave']) {
            return '#d43f3a';
        }
        
        if ($display['is_swapped']) {
            return '#eea236';
        }
        
        return '#4cae4c';
    }
}