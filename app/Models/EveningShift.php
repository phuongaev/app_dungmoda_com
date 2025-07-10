<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Encore\Admin\Auth\Database\Administrator;

class EveningShift extends Model
{
    use HasFactory;

    protected $fillable = [
        'admin_user_id',
        'shift_date',
    ];

    /**
     * Lấy thông tin nhân viên được phân công.
     */
    public function user()
    {
        return $this->belongsTo(Administrator::class, 'admin_user_id');
    }
}