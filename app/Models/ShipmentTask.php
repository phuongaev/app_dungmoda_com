<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShipmentTask extends Model
{
    use HasFactory;

    protected $table = 'shipment_tasks';

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $fillable = [
        'shipment_id',
        'profile_id',
        'status',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Status constants
    const STATUS_WAIT = 'wait';
    const STATUS_RUNNING = 'running';
    const STATUS_DONE = 'done';

    /**
     * Get all available status options
     *
     * @return array
     */
    public static function getStatusOptions()
    {
        return [
            self::STATUS_WAIT => 'Chờ xử lý',
            self::STATUS_RUNNING => 'Đang chạy',
            self::STATUS_DONE => 'Hoàn thành',
        ];
    }

    /**
     * Get status label in Vietnamese
     *
     * @return string
     */
    public function getStatusLabelAttribute()
    {
        return self::getStatusOptions()[$this->status] ?? $this->status;
    }

    /**
     * Relationship: Belongs to PosOrder via shipment_id
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function posOrder()
    {
        return $this->belongsTo(PosOrder::class, 'shipment_id', 'shipment_id');
    }

    /**
     * Relationship: Belongs to Customer via profile_id
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'profile_id', 'profile_id');
    }

    /**
     * Scope: Filter by profile_id
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $profileId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByProfile($query, $profileId)
    {
        return $query->where('profile_id', $profileId);
    }

    /**
     * Scope: Filter by status
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $status
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Get waiting tasks for specific profile
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $profileId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWaitingForProfile($query, $profileId)
    {
        return $query->where('profile_id', $profileId)
                    ->where('status', self::STATUS_WAIT);
    }

    /**
     * Scope: Get running tasks for specific profile
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @param string $profileId
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRunningForProfile($query, $profileId)
    {
        return $query->where('profile_id', $profileId)
                    ->where('status', self::STATUS_RUNNING);
    }

    /**
     * Scope: Get waiting tasks
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeWaiting($query)
    {
        return $query->where('status', self::STATUS_WAIT);
    }

    /**
     * Scope: Get running tasks
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeRunning($query)
    {
        return $query->where('status', self::STATUS_RUNNING);
    }

    /**
     * Scope: Get completed tasks
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     * @return \Illuminate\Database\Eloquent\Builder
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_DONE);
    }

    /**
     * Mark task as running
     *
     * @return bool
     */
    public function markAsRunning()
    {
        return $this->update(['status' => self::STATUS_RUNNING]);
    }

    /**
     * Mark task as completed
     *
     * @return bool
     */
    public function markAsCompleted()
    {
        return $this->update(['status' => self::STATUS_DONE]);
    }

    /**
     * Reset task to waiting status
     *
     * @return bool
     */
    public function resetToWaiting()
    {
        return $this->update(['status' => self::STATUS_WAIT]);
    }
}