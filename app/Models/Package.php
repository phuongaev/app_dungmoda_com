<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Package extends Model
{
    use HasFactory;

    protected $fillable = [
        'package_code',
        'shipping_partner',
        'notes',
        'package_status'
    ];

    public function shipments()
    {
        return $this->belongsToMany(Shipment::class, 'package_shipments');
    }

    public function getPackageStatusLabelAttribute()
    {
        $labels = [
            'pending' => 'Chờ xử lý',
            'in_transit' => 'Đang vận chuyển',
            'delivered_vn' => 'Nhập kho VN',
            'delivered' => 'Đã nhận hàng',
            'cancelled' => 'Đã hủy'
        ];
        return $labels[$this->package_status] ?? $this->package_status;
    }

    public function getShippingPartnerLabelAttribute()
    {
        $labels = [
            'atan' => 'A Tần',
            'oanh' => 'Oanh',
            'other' => 'Khác',
            'nga' => 'Nga',
            'fe' => 'Xuân Phê'
        ];
        return $labels[$this->shipping_partner] ?? $this->shipping_partner;
    }

    public function getShipmentsCountAttribute()
    {
        return $this->shipments()->count();
    }
}