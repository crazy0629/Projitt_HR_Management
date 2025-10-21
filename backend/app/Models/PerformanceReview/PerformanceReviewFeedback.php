<?php

namespace App\Models\PerformanceReview;

use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class PerformanceReviewFeedback extends Model
{
    use HasFactory;

    protected $table = 'performance_review_feedback';

    protected $fillable = [
        'review_id',
        'strengths',
        'opportunities',
        'ai_summary',
        'manager_summary',
        'development_recommendations',
        'key_themes',
        'sentiment',
        'generated_by',
        'is_ai_generated',
        'generated_at',
    ];

    protected $casts = [
        'key_themes' => 'array',
        'is_ai_generated' => 'boolean',
        'generated_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $dates = [
        'generated_at',
        'created_at',
        'updated_at',
    ];

    // Relationships
    public function review()
    {
        return $this->belongsTo(PerformanceReview::class, 'review_id');
    }

    public function generator()
    {
        return $this->belongsTo(User::class, 'generated_by');
    }

    // Scopes
    public function scopeAiGenerated($query)
    {
        return $query->where('is_ai_generated', true);
    }

    public function scopeManuallyGenerated($query)
    {
        return $query->where('is_ai_generated', false);
    }

    public function scopeBySentiment($query, $sentiment)
    {
        return $query->where('sentiment', $sentiment);
    }

    // Helper methods
    public function getSentimentLabel()
    {
        $labels = [
            'positive' => 'Positive',
            'neutral' => 'Neutral',
            'needs_attention' => 'Needs Attention',
        ];

        return $labels[$this->sentiment] ?? 'Unknown';
    }

    public function getSentimentColor()
    {
        $colors = [
            'positive' => 'success',
            'neutral' => 'warning',
            'needs_attention' => 'danger',
        ];

        return $colors[$this->sentiment] ?? 'secondary';
    }

    public function getFormattedKeyThemesAttribute()
    {
        return $this->key_themes ? implode(', ', $this->key_themes) : 'None identified';
    }

    public function generateAISummary()
    {
        // This would integrate with an AI service to generate feedback
        // For now, we'll compile from the review scores
        $review = $this->review;
        $scores = $review->scores()->completed()->get();

        if ($scores->isEmpty()) {
            return null;
        }

        // Compile strengths from all reviewers
        $allStrengths = $scores->pluck('strengths')->filter()->toArray();
        $allOpportunities = $scores->pluck('opportunities')->filter()->toArray();
        $allComments = $scores->pluck('comments')->filter()->toArray();

        // Generate AI summary (simplified version)
        $this->strengths = $this->compileFeedback($allStrengths);
        $this->opportunities = $this->compileFeedback($allOpportunities);
        $this->ai_summary = $this->generateOverallSummary($scores, $allComments);
        $this->key_themes = $this->extractKeyThemes($allComments);
        $this->sentiment = $this->analyzeSentiment($review->final_score);
        $this->is_ai_generated = true;
        $this->generated_at = now();

        $this->save();

        return $this;
    }

    private function compileFeedback($feedbackArray)
    {
        if (empty($feedbackArray)) {
            return null;
        }

        // Remove duplicates and empty entries
        $uniqueFeedback = array_unique(array_filter($feedbackArray));

        // For a simple implementation, we'll join with bullets
        return implode("\n• ", $uniqueFeedback);
    }

    private function generateOverallSummary($scores, $comments)
    {
        $avgScore = $scores->avg('average_score');
        $reviewCount = $scores->count();

        $summary = "Based on {$reviewCount} review(s), this employee received an average score of ".number_format($avgScore, 1).'/5.0. ';

        if ($avgScore >= 4.0) {
            $summary .= 'This indicates strong performance across most competencies.';
        } elseif ($avgScore >= 3.0) {
            $summary .= 'This indicates solid performance with room for growth in some areas.';
        } else {
            $summary .= 'This indicates areas requiring focused development and support.';
        }

        return $summary;
    }

    private function extractKeyThemes($comments)
    {
        // Simple keyword extraction (in production, use NLP)
        $commonThemes = [
            'communication', 'leadership', 'teamwork', 'innovation',
            'technical skills', 'problem solving', 'time management',
            'collaboration', 'mentoring', 'strategic thinking',
        ];

        $allText = strtolower(implode(' ', $comments));
        $foundThemes = [];

        foreach ($commonThemes as $theme) {
            if (strpos($allText, str_replace(' ', '', strtolower($theme))) !== false ||
                strpos($allText, strtolower($theme)) !== false) {
                $foundThemes[] = ucwords($theme);
            }
        }

        return array_unique($foundThemes);
    }

    private function analyzeSentiment($finalScore)
    {
        if ($finalScore >= 4.0) {
            return 'positive';
        } elseif ($finalScore >= 2.5) {
            return 'neutral';
        } else {
            return 'needs_attention';
        }
    }

    public function addManagerSummary($summary, $userId)
    {
        $this->manager_summary = $summary;
        $this->generated_by = $userId;
        $this->save();

        return $this;
    }

    public function addDevelopmentRecommendations($recommendations)
    {
        $this->development_recommendations = $recommendations;
        $this->save();

        return $this;
    }
}
