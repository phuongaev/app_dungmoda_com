<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class FanPage extends Model
{
    // protected $table = 'fan_pages';

    // Liên kết One-to-One tới Dataset qua dataset_id
    public function dataset()
    {
        return $this->belongsTo(Dataset::class, 'dataset_id', 'dataset_id');
    }
}
