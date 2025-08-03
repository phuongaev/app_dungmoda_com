<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FanPage extends Model
{
    protected $table = 'fan_pages';
    
    // Sử dụng page_id làm khóa chính
    protected $primaryKey = 'page_id';
    
    // Chỉ định các trường có thể mass assignment
    protected $fillable = [
        'page_id',
        'page_name',
        'page_short_name',
        'dataset_id',
        'instagram_id',
        'pancake_token',
        'botcake_token'
    ];

    // Liên kết One-to-One tới Dataset qua dataset_id
    public function dataset()
    {
        return $this->belongsTo(Dataset::class, 'dataset_id', 'dataset_id');
    }
}