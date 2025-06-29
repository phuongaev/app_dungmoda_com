<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cash extends Model
{
    use SoftDeletes;

    protected $table = "cashs";

    public static $THU = 0;
    public static $CHI = 1;

    protected $guarded = ['id'];

    public function labels ()
    {
        return $this->belongsToMany(Label::class, 'cash_labels');
    }
}
