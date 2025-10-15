<?php

namespace App\Models\Talent;

use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'actor_id',
        'entity_type',
        'entity_id',
        'action',
        'payload_json',
        'created_at',
    ];

    protected $casts = [
        'payload_json' => 'array',
        'created_at' => 'datetime',
    ];

    public $timestamps = false; // We only need created_at

    // Relationships
    public function actor()
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    // Scopes
    public function scopeForEntity($query, $entityType, $entityId)
    {
        return $query->where('entity_type', $entityType)->where('entity_id', $entityId);
    }

    public function scopeByActor($query, $actorId)
    {
        return $query->where('actor_id', $actorId);
    }

    public function scopeByAction($query, $action)
    {
        return $query->where('action', $action);
    }

    // Helper Methods
    public function getFormattedPayload()
    {
        return $this->payload_json ?? [];
    }

    public function getEntityName()
    {
        return class_basename($this->entity_type);
    }
}
