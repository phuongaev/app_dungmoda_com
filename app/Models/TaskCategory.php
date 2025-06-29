<?php
// app/Models/TaskCategory.php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TaskCategory extends Model
{
    protected $fillable = [
        'name', 'color', 'icon', 'sort_order', 'is_active'
    ];

    protected $casts = [
        'is_active' => 'boolean'
    ];

    public function dailyTasks()
    {
        return $this->hasMany(DailyTask::class, 'category_id');
    }
}