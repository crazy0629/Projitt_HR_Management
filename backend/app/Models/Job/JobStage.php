<?php

namespace App\Models\Job;

use App\Models\Master\Master;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class JobStage extends Model
{
    use SoftDeletes;

    protected $table = 'job_stages';

    protected $fillable = [
        'name',
        'order',       // or 'sort_order'
        'type_id',
        'job_id',
        'sub_type_id',
        'created_by',
        'updated_by',
        'deleted_by',
    ];

    /* -----------------------------
     | Relationships
     |----------------------------- */

    // Belongs to a master type
    public function type()
    {
        return $this->belongsTo(Master::class, 'type_id');
    }

    // Belongs to a master sub_type
    public function subType()
    {
        return $this->belongsTo(Master::class, 'sub_type_id');
    }

    /* -----------------------------
     | Query Scopes
     |----------------------------- */

    // Always order by "order" column
    public function scopeOrdered($query)
    {
        return $query->orderBy('order', 'asc');
    }
}
