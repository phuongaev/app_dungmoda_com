<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Encore\Admin\Auth\Database\Administrator;

class ShiftSwapRequest extends Model
{
    use HasFactory;

    protected $fillable = [
        'requester_id',
        'requester_shift_id',
        'target_user_id',
        'target_shift_id',
        'reason',
        'status',
        'admin_notes',
        'approved_by',
        'approved_at',
        'original_requester_shift_date',
        'original_target_shift_date'
    ];

    protected $dates = [
        'approved_at',
        'original_requester_shift_date',
        'original_target_shift_date'
    ];

    protected $casts = [
        'approved_at' => 'datetime',
        'original_requester_shift_date' => 'date',
        'original_target_shift_date' => 'date'
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
     * Relationship với người yêu cầu hoán đổi
     */
    public function requester()
    {
        return $this->belongsTo(Administrator::class, 'requester_id');
    }

    /**
     * Relationship với ca trực của người yêu cầu
     */
    public function requesterShift()
    {
        return $this->belongsTo(EveningShift::class, 'requester_shift_id');
    }

    /**
     * Relationship với người được đề xuất hoán đổi
     */
    public function targetUser()
    {
        return $this->belongsTo(Administrator::class, 'target_user_id');
    }

    /**
     * Relationship với ca trực của người được đề xuất
     */
    public function targetShift()
    {
        return $this->belongsTo(EveningShift::class, 'target_shift_id');
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
                    ->where('request_type', 'swap')
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
     * Scope để lọc đơn chờ duyệt
     */
    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    /**
     * Scope để lọc đơn đã được duyệt
     */
    public function scopeApproved($query)
    {
        return $query->where('status', self::STATUS_APPROVED);
    }

    /**
     * Lấy thông tin tóm tắt hoán đổi
     */
    public function getSwapSummaryAttribute()
    {
        $requesterName = $this->requester->name ?? 'N/A';
        $targetName = $this->targetUser->name ?? 'N/A';
        $requesterDate = $this->original_requester_shift_date ? $this->original_requester_shift_date->format('d/m/Y') : 'N/A';
        $targetDate = $this->original_target_shift_date ? $this->original_target_shift_date->format('d/m/Y') : 'N/A';

        return "{$requesterName} ({$requesterDate}) ↔ {$targetName} ({$targetDate})";
    }

    /**
     * Lưu lịch sử thay đổi
     */
    public function addHistory($action, $adminUserId, $notes = null)
    {
        return RequestHistory::create([
            'request_type' => 'swap',
            'request_id' => $this->id,
            'action' => $action,
            'admin_user_id' => $adminUserId,
            'notes' => $notes
        ]);
    }

    /**
     * Thực hiện hoán đổi ca (khi duyệt)
     */
    public function executeSwap()
    {
        if ($this->status !== self::STATUS_APPROVED) {
            return false;
        }

        try {
            \DB::beginTransaction();

            // Backup thông tin gốc nếu chưa có
            if (!$this->original_requester_shift_date) {
                $this->original_requester_shift_date = $this->requesterShift->shift_date;
            }
            if (!$this->original_target_shift_date) {
                $this->original_target_shift_date = $this->targetShift->shift_date;
            }
            $this->save();

            // Hoán đổi user_id của 2 ca
            $tempUserId = $this->requesterShift->admin_user_id;
            $this->requesterShift->admin_user_id = $this->targetShift->admin_user_id;
            $this->targetShift->admin_user_id = $tempUserId;

            $this->requesterShift->save();
            $this->targetShift->save();

            \DB::commit();
            return true;

        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('Error executing shift swap: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Khôi phục ca trực về trạng thái ban đầu (khi hủy)
     */
    public function revertSwap()
    {
        if ($this->status !== self::STATUS_APPROVED) {
            return false;
        }

        try {
            \DB::beginTransaction();

            // Tìm user gốc dựa trên original dates
            $originalRequesterShift = EveningShift::where('shift_date', $this->original_requester_shift_date)->first();
            $originalTargetShift = EveningShift::where('shift_date', $this->original_target_shift_date)->first();

            if ($originalRequesterShift && $originalTargetShift) {
                $originalRequesterShift->admin_user_id = $this->requester_id;
                $originalTargetShift->admin_user_id = $this->target_user_id;

                $originalRequesterShift->save();
                $originalTargetShift->save();
            }

            \DB::commit();
            return true;

        } catch (\Exception $e) {
            \DB::rollBack();
            \Log::error('Error reverting shift swap: ' . $e->getMessage());
            return false;
        }
    }
}