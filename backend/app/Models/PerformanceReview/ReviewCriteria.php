<?php

namespace App\Models\PerformanceReview;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ReviewCriteria extends Model
{
    use HasFactory;

    protected $fillable = [
        'competency_id',
        'text',
        'weight',
        'sort_order',
        'is_required',
        'is_active',
    ];

    protected $casts = [
        'weight' => 'decimal:2',
        'is_required' => 'boolean',
        'is_active' => 'boolean',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    // Relationships
    public function competency()
    {
        return $this->belongsTo(ReviewCompetency::class, 'competency_id');
    }

    public function cycle()
    {
        return $this->competency->cycle();
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeRequired($query)
    {
        return $query->where('is_required', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('sort_order')->orderBy('text');
    }

    public function scopeByCompetency($query, $competencyId)
    {
        return $query->where('competency_id', $competencyId);
    }

    // Helper methods
    public function isActive()
    {
        return $this->is_active;
    }

    public function isRequired()
    {
        return $this->is_required;
    }

    public function getFormattedWeight()
    {
        return $this->weight ? number_format($this->weight, 1).'%' : 'No weight';
    }

    public function updateSortOrder($newOrder)
    {
        $this->sort_order = $newOrder;
        $this->save();

        return $this;
    }

    public function duplicate($newCompetencyId)
    {
        $newCriteria = $this->replicate();
        $newCriteria->competency_id = $newCompetencyId;
        $newCriteria->save();

        return $newCriteria;
    }

    public function toggle()
    {
        $this->is_active = ! $this->is_active;
        $this->save();

        return $this;
    }

    // Static methods
    public static function createForCompetency($competencyId, $criteriaData)
    {
        $criteria = [];
        foreach ($criteriaData as $index => $data) {
            $criteria[] = self::create([
                'competency_id' => $competencyId,
                'text' => $data['text'],
                'weight' => $data['weight'] ?? 0,
                'sort_order' => ($index + 1) * 10,
                'is_required' => $data['is_required'] ?? true,
                'is_active' => $data['is_active'] ?? true,
            ]);
        }

        return $criteria;
    }

    public static function reorderForCompetency($competencyId, $criteriaIds)
    {
        foreach ($criteriaIds as $index => $criteriaId) {
            self::where('id', $criteriaId)
                ->where('competency_id', $competencyId)
                ->update(['sort_order' => ($index + 1) * 10]);
        }

        return true;
    }

    // Default criteria for common competencies
    public static function getDefaultCriteriaForCompetency($competencyName)
    {
        $defaultCriteria = [
            'Leadership' => [
                'Demonstrates clear vision and strategic thinking',
                'Motivates and inspires team members',
                'Makes effective decisions under pressure',
                'Takes responsibility for team outcomes',
                'Provides constructive feedback and coaching',
            ],
            'Communication' => [
                'Communicates clearly and effectively in writing',
                'Presents ideas confidently and persuasively',
                'Listens actively and responds appropriately',
                'Adapts communication style to different audiences',
                'Facilitates productive discussions and meetings',
            ],
            'Teamwork' => [
                'Collaborates effectively with diverse team members',
                'Shares knowledge and resources willingly',
                'Supports team goals over individual interests',
                'Resolves conflicts constructively',
                'Builds positive working relationships',
            ],
            'Problem Solving' => [
                'Identifies problems and root causes accurately',
                'Develops creative and practical solutions',
                'Uses data and analysis to inform decisions',
                'Implements solutions effectively',
                'Learns from setbacks and adapts approach',
            ],
            'Technical Skills' => [
                'Demonstrates required technical competencies',
                'Stays current with industry trends and tools',
                'Applies technical knowledge to solve problems',
                'Shares technical expertise with others',
                'Continuously improves technical skills',
            ],
        ];

        return $defaultCriteria[$competencyName] ?? [];
    }

    public static function createDefaultForCompetency($competencyId, $competencyName)
    {
        $defaultTexts = self::getDefaultCriteriaForCompetency($competencyName);
        $criteria = [];

        foreach ($defaultTexts as $index => $text) {
            $criteria[] = self::create([
                'competency_id' => $competencyId,
                'text' => $text,
                'weight' => 20, // Default equal weight
                'sort_order' => ($index + 1) * 10,
                'is_required' => true,
                'is_active' => true,
            ]);
        }

        return $criteria;
    }
}
