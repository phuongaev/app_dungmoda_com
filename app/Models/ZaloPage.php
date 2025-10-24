<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class ZaloPage extends Model
{
    use HasFactory, SoftDeletes;

    protected $table = 'zalo_pages';

    protected $fillable = [
        'phone_number',
        'global_id',
        'sdob',
        'zalo_name',
        'avatar_url',
        'pc_page_id',
        'pc_user_name',
    ];

    protected $dates = [
        'sdob',
        'created_at',
        'updated_at',
        'deleted_at',
    ];

    protected $casts = [
        'sdob' => 'date',
    ];
}