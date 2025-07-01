<?php

namespace App\Models;

use Encore\Admin\Auth\Database\Administrator as BaseAdministrator;

class Administrator extends BaseAdministrator
{
    // Thêm 'is_active' vào mảng fillable để có thể cập nhật hàng loạt
    protected $fillable = [
        'username',
        'password',
        'name',
        'avatar',
        'is_active',
    ];
}