<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Encore\Admin\Auth\Database\Administrator;

class RequestHistory extends Model
{
    use HasFactory;

    protected $fillable = [
        'request_type',
        'request_id',
        'action',
        'admin_user_id',
        'notes'
    ];

    // Request type constants
    const TYPE_LEAVE = 'leave';
    const TYPE_SWAP = 'swap';

    // Action constants
    const ACTION_SUBMITTED = 'submitted';
    const ACTION_APPROVED = 'approved';
    const ACTION_REJECTED = 'rejected';
    const ACTION_CANCELLED = 'cancelled';

    public static function getRequestTypes()
    {
        return [
            self::TYPE_LEAVE => 'Xin nghỉ',
            self::TYPE_SWAP => 'Hoán đổi ca'
        ];
    }

    public static function getActions()
    {
        return [
            self::ACTION_SUBMITTED => 'Gửi đơn',
            self::ACTION_APPROVED => 'Duyệt',
            self::ACTION_REJECTED => 'Từ chối',
            self::ACTION_CANCELLED => 'Hủy'
        ];
    }

    /**
     * Relationship với admin thực hiện hành động
     */
    public function admin()
    {
        return $this->belongsTo(Administrator::class, 'admin_user_id');
    }

    /**
     * Relationship với đơn xin nghỉ (polymorphic)
     */
    public function leaveRequest()
    {
        return $this->belongsTo(LeaveRequest::class, 'request_id')
                    ->where('request_type', self::TYPE_LEAVE);
    }

    /**
     * Relationship với đơn hoán đổi ca (polymorphic)
     */
    public function shiftSwapRequest()
    {
        return $this->belongsTo(ShiftSwapRequest::class, 'request_id')
                    ->where('request_type', self::TYPE_SWAP);
    }

    /**
     * Lấy tên hành động với màu sắc
     */
    public function getActionBadgeAttribute()
    {
        $badges = [
            self::ACTION_SUBMITTED => '<span class="label label-info">Gửi đơn</span>',
            self::ACTION_APPROVED => '<span class="label label-success">Duyệt</span>',
            self::ACTION_REJECTED => '<span class="label label-danger">Từ chối</span>',
            self::ACTION_CANCELLED => '<span class="label label-default">Hủy</span>'
        ];

        return $badges[$this->action] ?? $this->action;
    }

    /**
     * Lấy tên loại đơn
     */
    public function getRequestTypeNameAttribute()
    {
        $types = self::getRequestTypes();
        return $types[$this->request_type] ?? $this->request_type;
    }

    /**
     * Scope để lọc theo loại đơn
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('request_type', $type);
    }

    /**
     * Scope để lọc theo hành động
     */
    public function scopeOfAction($query, $action)
    {
        return $query->where('action', $action);
    }
}