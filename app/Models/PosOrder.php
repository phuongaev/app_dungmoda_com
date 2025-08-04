<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PosOrder extends Model
{
    use HasFactory;

    protected $table = 'pos_orders';

    protected $fillable = [
        'order_id',
        'system_id',
        'shipment_id',
        'cod',
        'page_id',
        'order_link',
        'link_confirm_order',
        'customer_name',
        'customer_phone',
        'customer_id',
        'customer_fb_id',
        'customer_addresses',
        'province_name',
        'province_id',
        'district_name',
        'district_id',
        'commnue_name',
        'commune_id',
        'status',
        'sub_status',
        'status_name',
        'post_id',
        'order_sources',
        'order_sources_name',
        'conversation_id',
        'total_quantity',
        'items_length',
        'time_send_partner',
        'inserted_at',
        'pos_updated_at',
        'customer_inserted_at'
    ];

    protected $casts = [
        'cod' => 'decimal:2',
        'time_send_partner' => 'datetime',
        'pos_updated_at' => 'datetime',
        'inserted_at' => 'datetime',
    ];

    // Constants cho các trạng thái
    const STATUS_PENDING = 0;
    const STATUS_WAITING_GOODS = 7;
    const STATUS_ORDERED = 8;
    const STATUS_CONFIRMED = 9;
    const STATUS_WAITING_PRINT = 10;
    const STATUS_PRINTED = 11;
    const STATUS_PACKING = 12;
    const STATUS_WAITING_TRANSFER = 1;
    const STATUS_SHIPPED = 2;
    const STATUS_RECEIVED = 3;
    const STATUS_RECEIVED_MONEY = 16;
    const STATUS_RETURNING = 4;
    const STATUS_PARTIAL_RETURN = 15;
    const STATUS_RETURNED = 5;
    const STATUS_CANCELED = 6;

    // Scope cho tìm kiếm tối ưu
    public function scopeSearchByPhone($query, $phone)
    {
        return $query->where('customer_phone', 'like', "%{$phone}%");
    }

    public function scopeSearchByOrderId($query, $orderId)
    {
        return $query->where('order_id', 'like', "%{$orderId}%");
    }

    // Scope tổng hợp cho tìm kiếm chính
    public function scopeQuickSearch($query, $keyword)
    {
        return $query->where(function ($q) use ($keyword) {
            $q->where('customer_phone', 'like', "%{$keyword}%")
              ->orWhere('order_id', 'like', "%{$keyword}%");
        });
    }

    // Scope cho filter theo trạng thái
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    // Scope cho filter theo nguồn đơn hàng
    public function scopeByOrderSource($query, $source)
    {
        return $query->where('order_sources', $source);
    }

    // Accessor cho format tiền
    public function getFormattedCodAttribute()
    {
        return number_format($this->cod, 0, ',', '.') . ' VND';
    }

    // Accessor cho format phone
    public function getFormattedPhoneAttribute()
    {
        $phone = $this->customer_phone;
        if (strlen($phone) === 10) {
            return substr($phone, 0, 4) . '***' . substr($phone, -3);
        }
        return $phone;
    }

    // Static methods cho options
    public static function getStatusOptions()
    {
        return [
            self::STATUS_PENDING => 'Chờ xử lý',
            self::STATUS_WAITING_GOODS => 'Chờ hàng',
            self::STATUS_ORDERED => 'Đã đặt hàng',
            self::STATUS_CONFIRMED => 'Đã xác nhận',
            self::STATUS_WAITING_PRINT => 'Chờ in',
            self::STATUS_PRINTED => 'Đã in',
            self::STATUS_PACKING => 'Đang đóng gói',
            self::STATUS_WAITING_TRANSFER => 'Chờ chuyển',
            self::STATUS_SHIPPED => 'Đã gửi hàng',
            self::STATUS_RECEIVED => 'Đã nhận',
            self::STATUS_RECEIVED_MONEY => 'Đã thu tiền',
            self::STATUS_RETURNING => 'Đang hoàn',
            self::STATUS_PARTIAL_RETURN => 'Hoàn một phần',
            self::STATUS_RETURNED => 'Đã hoàn',
            self::STATUS_CANCELED => 'Đã hủy',
        ];
    }

    public static function getStatusColors()
    {
        return [
            self::STATUS_PENDING => 'default',
            self::STATUS_WAITING_GOODS => 'warning',
            self::STATUS_ORDERED => 'info',
            self::STATUS_CONFIRMED => 'primary',
            self::STATUS_WAITING_PRINT => 'warning',
            self::STATUS_PRINTED => 'info',
            self::STATUS_PACKING => 'primary',
            self::STATUS_WAITING_TRANSFER => 'warning',
            self::STATUS_SHIPPED => 'info',
            self::STATUS_RECEIVED => 'success',
            self::STATUS_RECEIVED_MONEY => 'success',
            self::STATUS_RETURNING => 'danger',
            self::STATUS_PARTIAL_RETURN => 'warning',
            self::STATUS_RETURNED => 'danger',
            self::STATUS_CANCELED => 'danger',
        ];
    }

    // Relationship với PosOrderStatus cho cột status
    public function statusInfo()
    {
        return $this->belongsTo(PosOrderStatus::class, 'status', 'status_code');
    }

    // Relationship với PosOrderStatus cho cột dataset_status
    public function datasetStatusInfo()
    {
        return $this->belongsTo(PosOrderStatus::class, 'dataset_status', 'status_code');
    }

    // Override getStatusNameAttribute để lấy từ bảng statuses
    public function getStatusNameAttribute()
    {
        if ($this->statusInfo) {
            return $this->statusInfo->status_name;
        }
        return self::getStatusOptions()[$this->status] ?? 'Không xác định';
    }

    // Accessor để lấy màu trạng thái từ database hoặc fallback
    public function getStatusColorAttribute()
    {
        // Ưu tiên lấy từ relationship nếu có
        if ($this->relationLoaded('statusInfo') && $this->statusInfo) {
            return $this->statusInfo->status_color;
        }
        
        // Fallback về constants nếu không có relationship
        return self::getStatusColors()[$this->status] ?? 'default';
    }

    // Accessor để lấy tên dataset_status từ relationship
    public function getDatasetStatusNameAttribute()
    {
        if ($this->relationLoaded('datasetStatusInfo') && $this->datasetStatusInfo) {
            return $this->datasetStatusInfo->status_name;
        }
        return null;
    }

    // Accessor để lấy màu dataset_status từ relationship
    public function getDatasetStatusColorAttribute()
    {
        if ($this->relationLoaded('datasetStatusInfo') && $this->datasetStatusInfo) {
            return $this->datasetStatusInfo->status_color;
        }
        return 'default';
    }

    // Scope sử dụng relationship
    public function scopeWithStatusInfo($query)
    {
        return $query->with(['statusInfo', 'datasetStatusInfo']);
    }



    // =================== WORKFLOW RELATIONSHIPS ===================
    /**
     * Relationship với WorkflowHistory
     */
    public function workflowHistories()
    {
        return $this->hasMany(PosOrderWorkflowHistory::class, 'pos_order_id')
                    ->orderBy('executed_at', 'desc');
    }

    /**
     * Relationship với Workflow thông qua WorkflowHistory
     */
    public function workflows()
    {
        return $this->belongsToMany(
            Workflow::class, 
            'pos_order_workflow_histories', 
            'pos_order_id', 
            'workflow_id',
            'id',
            'workflow_id'
        )->withPivot(['workflow_status_id', 'executed_at'])
          ->withTimestamps()
          ->orderBy('pos_order_workflow_histories.executed_at', 'desc');
    }

    /**
     * Scope để filter đơn hàng chưa chạy workflow nào đó
     */
    public function scopeNotRunWorkflows($query, $workflowIds)
    {
        if (!is_array($workflowIds)) {
            $workflowIds = [$workflowIds];
        }

        return $query->whereDoesntHave('workflowHistories', function ($q) use ($workflowIds) {
            $q->whereIn('workflow_id', $workflowIds);
        });
    }

    /**
     * Scope để filter theo inserted_at
     */
    public function scopeInsertedBetween($query, $startDate, $endDate)
    {
        return $query->whereBetween('inserted_at', [$startDate, $endDate]);
    }

    /**
     * Scope để filter inserted_at nhỏ hơn hoặc bằng ngày
     */
    public function scopeInsertedBefore($query, $date)
    {
        return $query->where('inserted_at', '<=', $date);
    }

    /**
     * Scope để filter theo nhiều status
     */
    public function scopeByStatuses($query, $statuses)
    {
        if (!is_array($statuses)) {
            $statuses = [$statuses];
        }
        return $query->whereIn('status', $statuses);
    }

    /**
     * Check xem đơn hàng đã chạy workflow nào đó chưa
     */
    public function hasRunWorkflow($workflowId)
    {
        return $this->workflowHistories()
                    ->where('workflow_id', $workflowId)
                    ->exists();
    }

    /**
     * Lấy lần chạy workflow gần nhất
     */
    public function getLatestWorkflowRun($workflowId = null)
    {
        $query = $this->workflowHistories();
        
        if ($workflowId) {
            $query->where('workflow_id', $workflowId);
        }
        
        return $query->first();
    }

    /**
     * Đếm số lần chạy workflow
     */
    public function countWorkflowRuns($workflowId = null)
    {
        $query = $this->workflowHistories();
        
        if ($workflowId) {
            $query->where('workflow_id', $workflowId);
        }
        
        return $query->count();
    }


    /**
     * Render workflow history table for admin detail view
     *
     * @return string
     */
    public function renderWorkflowHistoryTable()
    {
        $histories = $this->workflowHistories()
                          ->with(['workflow', 'workflowStatus'])
                          ->orderBy('executed_at', 'desc')
                          ->get();

        if ($histories->isEmpty()) {
            return '<div class="alert alert-info">
                        <i class="fa fa-info-circle"></i> 
                        Đơn hàng này chưa chạy workflow nào.
                    </div>';
        }

        // Prepare data for table
        $tableData = [];
        foreach ($histories as $history) {
            $tableData[] = [
                'workflow_name' => $history->workflow ? $history->workflow->workflow_name : 'N/A',
                'workflow_id' => $history->workflow_id,
                'workflow_status' => $history->workflowStatus ? $history->workflowStatus->name : 'N/A',
                'executed_at' => $history->executed_at ? $history->executed_at->format('d/m/Y H:i:s') : 'N/A',
                'created_at' => $history->created_at ? $history->created_at->format('d/m/Y H:i:s') : 'N/A'
            ];
        }

        // Create table widget
        $headers = ['Tên Workflow', 'Workflow ID', 'Trạng thái', 'Thời gian thực hiện', 'Ngày tạo'];
        $table = new \Encore\Admin\Widgets\Table($headers, $tableData);

        return (new \Encore\Admin\Widgets\Box('Lịch sử Workflow', $table->render()))->render();
    }

}