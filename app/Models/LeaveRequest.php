<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Encore\Admin\Auth\Database\Administrator;
use Carbon\Carbon;

class LeaveRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'admin_user_id',
        'start_date',
        'end_date',
        'reason',
        'attachment_file',
        'status',
        'admin_notes',
        'approved_by',
        'approved_at'
    ];

    protected $dates = [
        'start_date',
        'end_date',
        'approved_at'
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'approved_at' => 'datetime'
    ];

    // Status constants
    const STATUS_PENDING = 'pending';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';
    const STATUS_CANCELLED = 'cancelled';

    public static function getStatuses()
    {
        return [
            self::STATUS_PENDING => 'Chờ duyệt',
            self::STATUS_APPROVED => 'Đã duyệt',
            self::STATUS_REJECTED => 'Từ chối',
            self::STATUS_CANCELLED => 'Đã hủy'
        ];
    }

    /**
     * Relationship với nhân viên xin nghỉ
     */
    public function employee()
    {
        return $this->belongsTo(Administrator::class, 'admin_user_id');
    }

    /**
     * Relationship với admin duyệt
     */
    public function approver()
    {
        return $this->belongsTo(Administrator::class, 'approved_by');
    }

    /**
     * Relationship với lịch sử
     */
    public function histories()
    {
        return $this->hasMany(RequestHistory::class, 'request_id')
                    ->where('request_type', 'leave')
                    ->orderBy('created_at', 'desc');
    }

    /**
     * Kiểm tra xem đơn có thể hủy không
     */
    public function canBeCancelled()
    {
        return in_array($this->status, [self::STATUS_PENDING]);
    }

    /**
     * Kiểm tra xem đơn có thể chỉnh sửa không
     */
    public function canBeEdited()
    {
        return $this->status === self::STATUS_PENDING;
    }

    /**
     * Tính số ngày nghỉ
     */
    public function getTotalDaysAttribute()
    {
        if (!$this->start_date || !$this->end_date) {
            return 0;
        }
        
        return $this->start_date->diffInDays($this->end_date) + 1;
    }

    /**
     * Lấy trạng thái với màu sắc
     */
    public function getStatusBadgeAttribute()
    {
        $badges = [
            self::STATUS_PENDING => '<span class="label label-warning">Chờ duyệt</span>',
            self::STATUS_APPROVED => '<span class="label label-success">Đã duyệt</span>',
            self::STATUS_REJECTED => '<span class="label label-danger">Từ chối</span>',
            self::STATUS_CANCELLED => '<span class="label label-default">Đã hủy</span>'
        ];

        return $badges[$this->status] ?? $this->status;
    }

    /**
     * Scope để lọc đơn trong khoảng thời gian
     */
    public function scopeInDateRange($query, $startDate, $endDate)
    {
        return $query->where(function ($q) use ($startDate, $endDate) {
            $q->whereBetween('start_date', [$startDate, $endDate])
              ->orWhereBetween('end_date', [$startDate, $endDate])
              ->orWhere(function ($qq) use ($startDate, $endDate) {
                  $qq->where('start_date', '<=', $startDate)
                     ->where('end_date', '>=', $endDate);
              });
        });
    }

    /**
     * Scope để lọc đơn đã được duyệt
     */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * Scope để lọc đơn chờ duyệt
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Kiểm tra xem có đang trong thời gian nghỉ không
     */
    public function isActiveLeave()
    {
        if ($this->status !== self::STATUS_APPROVED) {
            return false;
        }

        $today = Carbon::today();
        return $today->between($this->start_date, $this->end_date);
    }

    /**
     * Lưu lịch sử thay đổi
     */
    public function addHistory($action, $adminUserId, $notes = null)
    {
        return RequestHistory::create([
            'request_type' => 'leave',
            'request_id' => $this->id,
            'action' => $action,
            'admin_user_id' => $adminUserId,
            'notes' => $notes
        ]);
    }
}