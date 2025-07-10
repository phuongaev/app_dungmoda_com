<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PosOrderWorkflowHistory extends Model
{
    use HasFactory;

    protected $table = 'pos_order_workflow_histories';

    protected $fillable = [
        'pos_order_id',
        'workflow_status_id',
        'workflow_id',
        'executed_at'
    ];

    protected $casts = [
        'executed_at' => 'datetime',
    ];

    /**
     * Relationship với PosOrder
     */
    public function posOrder()
    {
        return $this->belongsTo(PosOrder::class, 'pos_order_id');
    }

    /**
     * Relationship với BaseStatus (workflow status)
     */
    public function workflowStatus()
    {
        return $this->belongsTo(BaseStatus::class, 'workflow_status_id', 'status_id');
    }

    /**
     * Scope để lấy lịch sử theo đơn hàng
     */
    public function scopeForOrder($query, $orderId)
    {
        return $query->where('pos_order_id', $orderId);
    }

    /**
     * Scope để lấy lịch sử theo workflow status
     */
    public function scopeByWorkflowStatus($query, $statusId)
    {
        return $query->where('workflow_status_id', $statusId);
    }

    /**
     * Scope để lấy lịch sử theo workflow ID
     */
    public function scopeByWorkflowId($query, $workflowId)
    {
        return $query->where('workflow_id', $workflowId);
    }

    /**
     * Scope để lấy lịch sử trong khoảng thời gian
     */
    public function scopeInTimeRange($query, $startDate, $endDate = null)
    {
        $query->where('executed_at', '>=', $startDate);
        
        if ($endDate) {
            $query->where('executed_at', '<=', $endDate);
        }
        
        return $query;
    }

    /**
     * Scope để lấy lịch sử mới nhất trước
     */
    public function scopeLatestFirst($query)
    {
        return $query->orderBy('executed_at', 'desc');
    }

    /**
     * Static method để tạo lịch sử workflow
     */
    public static function createWorkflowHistory($posOrderId, $workflowStatusId, $workflowId = null, $executedAt = null)
    {
        return static::create([
            'pos_order_id' => $posOrderId,
            'workflow_status_id' => $workflowStatusId,
            'workflow_id' => $workflowId,
            'executed_at' => $executedAt ?? now()
        ]);
    }

    /**
     * Static method để kiểm tra đơn hàng đã chạy workflow chưa
     */
    public static function hasOrderRunWorkflow($posOrderId, $workflowId = null, $workflowStatusId = null, $daysBefore = null)
    {
        $query = static::where('pos_order_id', $posOrderId);
        
        if ($workflowId) {
            $query->where('workflow_id', $workflowId);
        }
        
        if ($workflowStatusId) {
            $query->where('workflow_status_id', $workflowStatusId);
        }
        
        if ($daysBefore) {
            $query->where('executed_at', '>=', now()->subDays($daysBefore));
        }
        
        return $query->exists();
    }

    /**
     * Accessor để hiển thị workflow status name
     */
    public function getWorkflowStatusNameAttribute()
    {
        return $this->workflowStatus->status_name ?? 'Không xác định';
    }

    /**
     * Accessor để format thời gian
     */
    public function getFormattedExecutedAtAttribute()
    {
        return $this->executed_at->format('d/m/Y H:i:s');
    }
}