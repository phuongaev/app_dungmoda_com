<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PosOrderStatus extends Model
{
    use HasFactory;

    protected $table = 'pos_order_statuses';

    protected $fillable = [
        'status_code',
        'status_name',
        'status_color',
        'description',
        'is_active',
        'sort_order'
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    // Scope cho active statuses
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    // Scope sắp xếp theo thứ tự
    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order');
    }

    // Relationship với PosOrder
    public function orders()
    {
        return $this->hasMany(PosOrder::class, 'status', 'status_code');
    }

    // Static method để lấy options cho select
    public static function getSelectOptions()
    {
        return static::active()
            ->ordered()
            ->pluck('status_name', 'status_code')
            ->toArray();
    }

    // Static method để lấy màu theo status code
    public static function getColorByStatusCode($statusCode)
    {
        $status = static::where('status_code', $statusCode)->first();
        return $status ? $status->status_color : 'default';
    }

    // Static method để lấy tên theo status code
    public static function getNameByStatusCode($statusCode)
    {
        $status = static::where('status_code', $statusCode)->first();
        return $status ? $status->status_name : 'Không xác định';
    }

    // Accessor cho label hiển thị
    public function getLabelAttribute()
    {
        return "<span class='label label-{$this->status_color}'>{$this->status_name}</span>";
    }

    // Static method để refresh cache
    public static function refreshCache()
    {
        // Implement cache logic nếu cần
        // Cache::forget('pos_order_statuses');
        return static::getSelectOptions();
    }
}