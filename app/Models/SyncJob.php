<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SyncJob extends Model
{
    protected $fillable = [
        'job_type',
        'status',
        'current_page',
        'total_pages',
        'total_records',
        'synced_records',
        'api_params',
        'error_message',
        'started_at',
        'completed_at',
    ];

    protected $casts = [
        'api_params' => 'array',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    const STATUS_PENDING = 'pending';
    const STATUS_RUNNING = 'running';
    const STATUS_COMPLETED = 'completed';
    const STATUS_FAILED = 'failed';
    const STATUS_PAUSED = 'paused';

    const JOB_TYPE_ORDERS_SYNC = 'orders_sync';

    // Scopes
    public function scopeOrders($query)
    {
        return $query->where('job_type', self::JOB_TYPE_ORDERS_SYNC);
    }

    public function scopeRunning($query)
    {
        return $query->where('status', self::STATUS_RUNNING);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    // Helper methods
    public function markAsRunning()
    {
        $this->update([
            'status' => self::STATUS_RUNNING,
            'started_at' => now(),
        ]);
    }

    public function markAsCompleted()
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'completed_at' => now(),
        ]);
    }

    public function markAsFailed($errorMessage = null)
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'error_message' => $errorMessage,
            'completed_at' => now(),
        ]);
    }

    public function markAsPaused()
    {
        $this->update([
            'status' => self::STATUS_PAUSED,
        ]);
    }

    public function updateProgress($currentPage, $syncedRecords, $totalPages = null, $totalRecords = null)
    {
        $updateData = [
            'current_page' => $currentPage,
            'synced_records' => $syncedRecords,
        ];

        if ($totalPages !== null) {
            $updateData['total_pages'] = $totalPages;
        }

        if ($totalRecords !== null) {
            $updateData['total_records'] = $totalRecords;
        }

        $this->update($updateData);
    }

    public function getProgressPercentage()
    {
        if (!$this->total_records || $this->total_records == 0) {
            return 0;
        }

        return round(($this->synced_records / $this->total_records) * 100, 2);
    }

    public function getEstimatedTimeRemaining()
    {
        if (!$this->started_at || !$this->synced_records || $this->synced_records == 0) {
            return null;
        }

        $elapsedMinutes = $this->started_at->diffInMinutes(now());
        $recordsPerMinute = $this->synced_records / $elapsedMinutes;
        
        if ($recordsPerMinute == 0) {
            return null;
        }

        $remainingRecords = $this->total_records - $this->synced_records;
        $estimatedMinutes = $remainingRecords / $recordsPerMinute;

        return round($estimatedMinutes);
    }

    // Static methods
    public static function createOrdersSyncJob($startPage = 1, $apiParams = [])
    {
        return static::create([
            'job_type' => self::JOB_TYPE_ORDERS_SYNC,
            'status' => self::STATUS_PENDING,
            'current_page' => $startPage,
            'api_params' => $apiParams,
        ]);
    }

    public static function getLastOrdersSyncJob()
    {
        return static::orders()->latest()->first();
    }

    public static function hasRunningJob()
    {
        return static::running()->exists();
    }
}