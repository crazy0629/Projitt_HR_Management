<?php

namespace App\Models\PerformanceReview;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReviewCompetency extends Model
{
    use HasFactory;

    protected $fillable = [
        'cycle_id',
        'name',
        'description',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function cycle()
    {
        return $this->belongsTo(PerformanceReviewCycle::class, 'cycle_id');
    }

    public function criteria()
    {
        return $this->hasMany(ReviewCriteria::class, 'competency_id')->orderBy('sort_order');
    }

    public function activeCriteria()
    {
        return $this->hasMany(ReviewCriteria::class, 'competency_id')
            ->where('is_active', true)
            ->orderBy('sort_order');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('name');
    }

    public function scopeByCycle($query, $cycleId)
    {
        return $query->where('cycle_id', $cycleId);
    }

    // Helper methods
    public function isActive()
    {
        return $this->is_active;
    }

    public function getCriteriaCount()
    {
        return $this->criteria()->count();
    }

    public function getActiveCriteriaCount()
    {
        return $this->activeCriteria()->count();
    }

    public function getTotalWeight()
    {
        return $this->activeCriteria()->sum('weight');
    }

    public function updateSortOrder($newOrder)
    {
        $this->sort_order = $newOrder;
        $this->save();

        return $this;
    }

    public function duplicate($newCycleId)
    {
        $newCompetency = $this->replicate();
        $newCompetency->cycle_id = $newCycleId;
        $newCompetency->save();

        // Duplicate all criteria
        foreach ($this->criteria as $criteria) {
            $criteria->duplicate($newCompetency->id);
        }

        return $newCompetency;
    }

    // Static methods
    public static function createDefault($cycleId)
    {
        $defaultCompetencies = [
            ['name' => 'Leadership', 'description' => 'Ability to guide and inspire others'],
            ['name' => 'Communication', 'description' => 'Effective verbal and written communication skills'],
            ['name' => 'Teamwork', 'description' => 'Collaboration and working effectively with others'],
            ['name' => 'Problem Solving', 'description' => 'Analytical thinking and solution development'],
            ['name' => 'Technical Skills', 'description' => 'Job-specific technical competencies'],
        ];

        $createdCompetencies = [];
        foreach ($defaultCompetencies as $index => $competency) {
            $createdCompetencies[] = self::create([
                'cycle_id' => $cycleId,
                'name' => $competency['name'],
                'description' => $competency['description'],
                'sort_order' => ($index + 1) * 10,
                'is_active' => true,
            ]);
        }

        return $createdCompetencies;
    }

    public static function reorderForCycle($cycleId, $competencyIds)
    {
        foreach ($competencyIds as $index => $competencyId) {
            self::where('id', $competencyId)
                ->where('cycle_id', $cycleId)
                ->update(['sort_order' => ($index + 1) * 10]);
        }

        return true;
    }
}
