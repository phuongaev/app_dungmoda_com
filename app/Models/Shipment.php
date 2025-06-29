<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shipment extends Model
{
    use HasFactory;

    protected $fillable = [
        'tracking_code',
        'notes',
        'shipment_status'
    ];

    public function importOrders()
    {
        return $this->belongsToMany(ImportOrder::class, 'import_order_shipments');
    }

    public function packages()
    {
        return $this->belongsToMany(Package::class, 'package_shipments');
    }

    public function getShipmentStatusLabelAttribute()
    {
        $labels = [
            'pending' => 'Chờ xử lý',
            'processing' => 'Đang xử lý',
            'shipped' => 'Đã gửi hàng',
            'delivered' => 'Đã giao hàng',
            'cancelled' => 'Đã hủy'
        ];
        return $labels[$this->shipment_status] ?? $this->shipment_status;
    }

    public function getOrderCodesAttribute()
    {
        return $this->importOrders->pluck('order_code')->implode(', ') ?: 'No Orders';
    }

    public function getPackageCodesAttribute()
    {
        return $this->packages->pluck('package_code')->implode(', ') ?: 'No Packages';
    }

    public function getShippingPartnerLabelAttribute()
    {
        $labels = [
            'atan' => 'A Tần',
            'other' => 'Khác',
            'oanh' => 'Oanh',
        ];
        return $labels[$this->shipping_partner] ?? $this->shipping_partner;
    }
    
}