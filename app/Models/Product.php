<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    // protected $table = 'products';

    // Quan hệ nhiều-nhiều tới MediaList
    public function mediaLists()
    {
        return $this->belongsToMany(MediaList::class, 'MediaProduct', 'product_id', 'media_list_id');
    }
}
