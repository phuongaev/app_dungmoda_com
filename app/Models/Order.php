<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;

class Order extends Model
{
    protected $fillable = [
        'order_id',
        'system_id',
        'pos_global_id',
        'account', 
        'status',
        'sub_status',
        'status_name',
        'customer_id',
        'fb_id',
        'bill_phone_number',
        'bill_full_name',
        'page_id',
        'page_name',
        'post_id',
        'conversation_id',
        'order_link',
        'link_confirm_order',
        'order_sources',
        'order_sources_name',
        'shipping_fee',
        'cod',
        'total_quantity',
        'items_length',
        'last_editor',
        'time_send_partner',
        'inserted_at',
        'api_updated_at',
    ];

    protected $casts = [
        'last_editor' => 'array',
        'shipping_fee' => 'decimal:2',
        'cod' => 'decimal:2',
        'inserted_at' => 'datetime',
        'api_updated_at' => 'datetime',
        'time_send_partner' => 'datetime',
    ];

    public function scopeBySystemId($query, $systemId)
    {
        if ($systemId !== null) {
            return $query->where('system_id', $systemId);
        }
        return $query;
    }

    public function scopeByPosGlobalId($query, $posGlobalId)
    {
        if ($posGlobalId) {
            return $query->where('pos_global_id', $posGlobalId);
        }
        return $query;
    }

    // Scopes for searching - Tối ưu hiệu suất
    public function scopeByPhone($query, $phone)
    {
        if ($phone) {
            // Nếu search exact phone number
            if (preg_match('/^\d+$/', $phone)) {
                return $query->where('bill_phone_number', $phone);
            }
            // Nếu search partial phone, sử dụng FULLTEXT index
            return $query->whereRaw('MATCH(bill_phone_number) AGAINST(? IN BOOLEAN MODE)', ["+{$phone}*"]);
        }
        return $query;
    }

    public function scopeByOrderId($query, $orderId)
    {
        if ($orderId) {
            // Nếu search exact order_id
            if (strlen($orderId) > 8) {
                return $query->where('order_id', $orderId);
            }
            // Nếu search partial order_id, sử dụng FULLTEXT index
            return $query->whereRaw('MATCH(order_id) AGAINST(? IN BOOLEAN MODE)', ["+{$orderId}*"]);
        }
        return $query;
    }

    public function scopeByCustomerName($query, $name)
    {
        if ($name) {
            // Sử dụng FULLTEXT index cho tìm kiếm tên
            return $query->whereRaw('MATCH(bill_full_name) AGAINST(? IN BOOLEAN MODE)', ["+{$name}*"]);
        }
        return $query;
    }

    public function scopeByStatus($query, $status)
    {
        if ($status !== null) {
            return $query->where('status', $status);
        }
        return $query;
    }

    public function scopeByPageId($query, $pageId)
    {
        if ($pageId) {
            return $query->where('page_id', $pageId);
        }
        return $query;
    }

    // Accessor for formatted dates
    public function getFormattedInsertedAtAttribute()
    {
        return $this->inserted_at ? $this->inserted_at->format('d/m/Y H:i') : '';
    }

    public function getFormattedApiUpdatedAtAttribute()
    {
        return $this->api_updated_at ? $this->api_updated_at->format('d/m/Y H:i') : '';
    }

    // Status helper methods
    public function getStatusTextAttribute()
    {
        return $this->status_name ?: $this->getDefaultStatusText();
    }

    private function getDefaultStatusText()
    {
        $statusMap = [
            0   => 'Mới',
            1   => 'Đã xác nhận',
            2   => 'Đã gửi hàng',
            3   => 'Đã nhận',
            4   => 'Đang hoàn',
            5   => 'Đã hoàn',
            6   => 'Hủy',
            8   => 'Đang đóng hàng',
            9   => 'Chờ chuyển hàng',
            11  => 'Chờ hàng',
            12  => 'Chờ in',
            13  => 'Đã in',
            16  => 'Đã thu tiền',
            15  => 'Hoàn một phần',
            20  => 'Đã đặt hàng',
        ];

        return $statusMap[$this->status] ?? 'Không xác định';
    }

    // Static method to create/update from API data
    public static function createOrUpdateFromApiData($apiData)
    {
        $orderData = [
            'order_id' => $apiData['id'],
            'system_id' => $apiData['system_id'] ?? null,
            'pos_global_id' => $apiData['pos_global_id'] ?? $apiData['global_id'] ?? null,
            'account' => $apiData['account'] ?? null,
            'status' => $apiData['status'],
            'sub_status' => $apiData['sub_status'] ?? null,
            'status_name' => $apiData['status_name'] ?? null,
            'customer_id' => $apiData['customer']['customer_id'] ?? null,
            'fb_id' => $apiData['customer']['fb_id'] ?? null,
            'bill_phone_number' => $apiData['bill_phone_number'] ?? null,
            'bill_full_name' => $apiData['bill_full_name'] ?? null,
            'page_id' => $apiData['page']['id'] ?? $apiData['page_id'] ?? null,
            'page_name' => $apiData['page']['name'] ?? null,
            'post_id' => $apiData['post_id'] ?? null,
            'conversation_id' => $apiData['conversation_id'] ?? null,
            'order_link' => $apiData['order_link'] ?? null,
            'link_confirm_order' => $apiData['link_confirm_order'] ?? null,
            'order_sources' => $apiData['order_sources'] ?? null,
            'order_sources_name' => $apiData['order_sources_name'] ?? null,
            'shipping_fee' => $apiData['shipping_fee'] ?? 0,
            'cod' => $apiData['cod'] ?? 0,
            'total_quantity' => $apiData['total_quantity'] ?? 0,
            'items_length' => $apiData['items_length'] ?? 0,
            'last_editor' => $apiData['last_editor'] ?? null,
            'time_send_partner' => $apiData['time_send_partner'] ? Carbon::parse($apiData['time_send_partner']) : null,
            'inserted_at' => $apiData['inserted_at'] ? Carbon::parse($apiData['inserted_at']) : null,
            'api_updated_at' => $apiData['updated_at'] ? Carbon::parse($apiData['updated_at']) : null,
        ];

        return static::updateOrCreate(
            ['order_id' => $orderData['order_id']],
            $orderData
        );
    }
}