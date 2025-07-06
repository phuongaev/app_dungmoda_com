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
        'page_id',
        'customer_name',
        'customer_phone',
        'customer_id',
        'customer_fb_id',
        'cod',
        'total_quantity',
        'items_length',
        'status',
        'sub_status',
        'status_name',
        'order_sources',
        'order_sources_name',
        'order_link',
        'link_confirm_order',
        'conversation_id',
        'post_id',
        'time_send_partner',
        'pos_updated_at'
    ];

    protected $casts = [
        'cod' => 'decimal:2',
        'time_send_partner' => 'datetime',
        'pos_updated_at' => 'datetime',
    ];

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

    // Status constants theo yêu cầu
    const STATUS_NEW = 0;
    const STATUS_WAITING_GOODS = 11;
    const STATUS_ORDERED = 20;
    const STATUS_CONFIRMED = 1;
    const STATUS_WAITING_PRINT = 12;
    const STATUS_PRINTED = 13;
    const STATUS_PACKING = 8;
    const STATUS_WAITING_TRANSFER = 9;
    const STATUS_SHIPPED = 2;
    const STATUS_RECEIVED = 3;
    const STATUS_RECEIVED_MONEY = 16;
    const STATUS_RETURNING = 4;
    const STATUS_PARTIAL_RETURN = 15;
    const STATUS_RETURNED = 5;
    const STATUS_CANCELED = 6;

    public static function getStatusOptions()
    {
        return [
            self::STATUS_NEW => 'Mới',
            self::STATUS_WAITING_GOODS => 'Chờ hàng',
            self::STATUS_ORDERED => 'Đã đặt hàng',
            self::STATUS_CONFIRMED => 'Đã xác nhận',
            self::STATUS_WAITING_PRINT => 'Chờ in',
            self::STATUS_PRINTED => 'Đã in',
            self::STATUS_PACKING => 'Đang đóng hàng',
            self::STATUS_WAITING_TRANSFER => 'Chờ chuyển hàng',
            self::STATUS_SHIPPED => 'Đã gửi hàng',
            self::STATUS_RECEIVED => 'Đã nhận',
            self::STATUS_RECEIVED_MONEY => 'Đã thu tiền',
            self::STATUS_RETURNING => 'Đang hoàn',
            self::STATUS_PARTIAL_RETURN => 'Hoàn một phần',
            self::STATUS_RETURNED => 'Đã hoàn',
            self::STATUS_CANCELED => 'Đã hủy',
        ];
    }

    // Phương thức lấy màu sắc cho status
    public static function getStatusColors()
    {
        return [
            self::STATUS_NEW => 'default',
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

    // Relationship với PosOrderStatus
    public function statusInfo()
    {
        return $this->belongsTo(PosOrderStatus::class, 'status', 'status_code');
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

    // Scope sử dụng relationship
    public function scopeWithStatusInfo($query)
    {
        return $query->with('statusInfo');
    }
}