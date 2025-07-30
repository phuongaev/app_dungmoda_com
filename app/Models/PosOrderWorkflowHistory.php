<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PosOrderWorkflowHistory extends Model
{
    use HasFactory;

    protected $table = 'pos_order_workflow_histories';

    // Disable updated_at timestamp
    const UPDATED_AT = null;

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
     * Relationship với Workflow
     */
    public function workflow()
    {
        return $this->belongsTo(Workflow::class, 'workflow_id', 'workflow_id');
    }

    /**
     * Relationship với BaseStatus (workflow_status_id)
     * Note: Chưa tạo foreign key constraint như trong migration
     */
    public function workflowStatus()
    {
        return $this->belongsTo(\App\Models\BaseStatus::class, 'workflow_status_id');
    }

    /**
     * Scope để filter theo đơn hàng
     */
    public function scopeByOrder($query, $orderId)
    {
        return $query->where('pos_order_id', $orderId);
    }

    /**
     * Scope để filter theo workflow
     */
    public function scopeByWorkflow($query, $workflowId)
    {
        return $query->where('workflow_id', $workflowId);
    }

    /**
     * Scope để filter theo thời gian thực hiện
     */
    public function scopeExecutedBetween($query, $startDate, $endDate)
    {
        return $query->whereBetween('executed_at', [$startDate, $endDate]);
    }

    /**
     * Scope để sắp xếp theo thời gian thực hiện mới nhất
     */
    public function scopeLatest($query)
    {
        return $query->orderBy('executed_at', 'desc');
    }

    /**
     * Scope để lấy lịch sử của đơn hàng với workflow info
     */
    public function scopeWithWorkflowInfo($query)
    {
        return $query->with(['workflow', 'workflowStatus']);
    }
}