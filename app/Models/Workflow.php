<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Workflow extends Model
{
    use HasFactory;

    protected $table = 'workflows';

    protected $fillable = [
        'workflow_id',
        'workflow_name', 
        'workflow_status'
    ];

    protected $casts = [
        'workflow_status' => 'string',
    ];

    // Constants cho workflow status
    const STATUS_ACTIVE = 'active';
    const STATUS_DEACTIVE = 'deactive';

    /**
     * Scope cho workflow đang hoạt động
     */
    public function scopeActive($query)
    {
        return $query->where('workflow_status', self::STATUS_ACTIVE);
    }

    /**
     * Scope cho workflow không hoạt động
     */
    public function scopeDeactive($query)
    {
        return $query->where('workflow_status', self::STATUS_DEACTIVE);
    }

    /**
     * Get status options for admin
     */
    public static function getStatusOptions()
    {
        return [
            self::STATUS_ACTIVE => 'Hoạt động',
            self::STATUS_DEACTIVE => 'Không hoạt động'
        ];
    }

    /**
     * Relationship với PosOrderWorkflowHistory
     */
    public function workflowHistories()
    {
        return $this->hasMany(PosOrderWorkflowHistory::class, 'workflow_id', 'workflow_id');
    }

    /**
     * Relationship với PosOrder thông qua WorkflowHistory
     */
    public function posOrders()
    {
        return $this->belongsToMany(
            PosOrder::class, 
            'pos_order_workflow_histories', 
            'workflow_id', 
            'pos_order_id',
            'workflow_id',
            'id'
        )->withPivot(['workflow_status_id', 'executed_at'])
          ->withTimestamps();
    }

    /**
     * Accessor cho formatted status
     */
    public function getFormattedStatusAttribute()
    {
        $statuses = self::getStatusOptions();
        return $statuses[$this->workflow_status] ?? $this->workflow_status;
    }
}