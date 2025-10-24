<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MediaList extends Model
{
    // protected $table = 'media_lists';

    // Thêm priority vào fillable để có thể mass assignment
    protected $fillable = [
        'source_id',
        'media_url',
        'local_url',
        'media_order',
        'type',
        'status',
        'priority'
    ];

    // Quan hệ nhiều-nhiều tới Product
    public function products()
    {
        return $this->belongsToMany(Product::class, 'media_products', 'media_list_id', 'product_id');
    }

    // Liên kết lấy variations_code, variations_name
    public function variations()
    {
        return $this->belongsToMany(Product::class, 'media_products', 'media_list_id', 'product_id');
    }

    // Quan hệ tới MediaSource
    public function source()
    {
        return $this->belongsTo(MediaSource::class, 'source_id', 'id');
    }

    // Xóa dữ liệu liên kết
    protected static function booted()
    {
        static::deleting(function ($mediaList) {
            $mediaList->products()->detach();
        });
    }
}