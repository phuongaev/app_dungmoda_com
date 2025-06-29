<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ImportOrder extends Model
{
    use HasFactory;

    protected $fillable = [
        'order_code',
        'pancake_id',
        'quantity',
        'quantity_bill',
        'supplier_code',
        'notes',
        'import_status'
    ];

    public function shipments()
    {
        return $this->belongsToMany(Shipment::class, 'import_order_shipments');
    }

    public function getImportStatusLabelAttribute()
    {
        $labels = [
            'pending' => 'Chờ xử lý',
            'processing' => 'Đang xử lý',
            'in_transit' => 'Đang vận chuyển',
            'completed' => 'Hoàn thành',
            'cancelled' => 'Đã hủy'
        ];
        return $labels[$this->import_status] ?? $this->import_status;
    }

    public function getShipmentsCountAttribute()
    {
        return $this->shipments()->count();
    }
    
}