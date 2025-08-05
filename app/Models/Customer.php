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


    /**
     * Relationship với ShipmentTask
     * One-to-Many relationship (một customer có thể có nhiều shipment tasks)
     */
    public function shipmentTasks()
    {
        return $this->hasMany(ShipmentTask::class, 'profile_id', 'profile_id');
    }

    /**
     * Scope để load shipment tasks info
     */
    public function scopeWithShipmentTasks($query)
    {
        return $query->with('shipmentTasks');
    }

    /**
     * Scope để filter customers có shipment tasks với status cụ thể
     */
    public function scopeWithShipmentTaskStatus($query, $status)
    {
        return $query->whereHas('shipmentTasks', function ($q) use ($status) {
            $q->where('status', $status);
        });
    }

    /**
     * Get waiting shipment tasks for this customer
     */
    public function getWaitingShipmentTasksAttribute()
    {
        return $this->shipmentTasks()->where('status', ShipmentTask::STATUS_WAIT)->get();
    }

    /**
     * Get running shipment tasks for this customer
     */
    public function getRunningShipmentTasksAttribute()
    {
        return $this->shipmentTasks()->where('status', ShipmentTask::STATUS_RUNNING)->get();
    }


    
}
