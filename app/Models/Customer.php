<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Customer extends Model
{
    use SoftDeletes;

    // protected $table = 'customers';

    // Quan hệ tới FanPages
    public function fanpage()
    {
        return $this->belongsTo(FanPage::class, 'page_id', 'page_id');
    }

    // Quan hệ tới OmniProfile
    public function profile()
    {
        return $this->belongsTo(OmniProfile::class, 'profile_id', 'profile_id');
    }

    // Quan hệ tới ZaloTask
    public function zaloTask()
    {
        return $this->belongsTo(ZaloTask::class, 'zalo_task_id', 'zalo_task_id');
    }

    // Quan hệ tới BaseStatus
    public function status()
    {
        return $this->belongsTo(BaseStatus::class, 'status_id', 'status_id');
    }
}
