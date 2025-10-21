<?php

namespace App\Models\PerformanceReview;

use App\Models\User\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

class ReviewQuestionImport extends Model
{
    use HasFactory;

    protected $fillable = [
        'cycle_id',
        'file_name',
        'file_path',
        'imported_count',
        'failed_count',
        'import_log',
        'status',
        'uploaded_by',
        'processed_at',
    ];

    protected $casts = [
        'import_log' => 'array',
        'processed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
    ];

    protected $dates = [
        'processed_at',
        'created_at',
        'updated_at',
    ];

    // Relationships
    public function cycle()
    {
        return $this->belongsTo(PerformanceReviewCycle::class, 'cycle_id');
    }

    public function uploader()
    {
        return $this->belongsTo(User::class, 'uploaded_by');
    }

    // Scopes
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', 'processing');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeFailed($query)
    {
        return $query->where('status', 'failed');
    }

    public function scopeByCycle($query, $cycleId)
    {
        return $query->where('cycle_id', $cycleId);
    }

    // Helper methods
    public function isPending()
    {
        return $this->status === 'pending';
    }

    public function isProcessing()
    {
        return $this->status === 'processing';
    }

    public function isCompleted()
    {
        return $this->status === 'completed';
    }

    public function isFailed()
    {
        return $this->status === 'failed';
    }

    public function getSuccessRate()
    {
        $total = $this->imported_count + $this->failed_count;

        return $total > 0 ? round(($this->imported_count / $total) * 100, 1) : 0;
    }

    public function getTotalRecords()
    {
        return $this->imported_count + $this->failed_count;
    }

    public function getFileSize()
    {
        if (Storage::exists($this->file_path)) {
            return Storage::size($this->file_path);
        }

        return 0;
    }

    public function getFormattedFileSize()
    {
        $bytes = $this->getFileSize();

        if ($bytes >= 1048576) {
            return number_format($bytes / 1048576, 2).' MB';
        } elseif ($bytes >= 1024) {
            return number_format($bytes / 1024, 2).' KB';
        } else {
            return $bytes.' bytes';
        }
    }

    public function markAsProcessing()
    {
        $this->status = 'processing';
        $this->save();

        return $this;
    }

    public function markAsCompleted($importedCount, $failedCount, $log = [])
    {
        $this->status = 'completed';
        $this->imported_count = $importedCount;
        $this->failed_count = $failedCount;
        $this->import_log = $log;
        $this->processed_at = now();
        $this->save();

        return $this;
    }

    public function markAsFailed($log = [])
    {
        $this->status = 'failed';
        $this->import_log = $log;
        $this->processed_at = now();
        $this->save();

        return $this;
    }

    public function addLogEntry($message, $type = 'info')
    {
        $log = $this->import_log ?? [];
        $log[] = [
            'message' => $message,
            'type' => $type,
            'timestamp' => now()->toISOString(),
        ];

        $this->import_log = $log;
        $this->save();

        return $this;
    }

    public function getFormattedLog()
    {
        if (! $this->import_log) {
            return [];
        }

        return collect($this->import_log)->map(function ($entry) {
            return [
                'message' => $entry['message'],
                'type' => $entry['type'],
                'time' => \Carbon\Carbon::parse($entry['timestamp'])->format('H:i:s'),
            ];
        })->toArray();
    }

    public function deleteFile()
    {
        if (Storage::exists($this->file_path)) {
            Storage::delete($this->file_path);
        }

        return $this;
    }

    // Static methods
    public static function createForUpload($cycleId, $file, $uploadedBy)
    {
        $fileName = $file->getClientOriginalName();
        $filePath = $file->store('review_imports');

        return self::create([
            'cycle_id' => $cycleId,
            'file_name' => $fileName,
            'file_path' => $filePath,
            'uploaded_by' => $uploadedBy,
            'status' => 'pending',
        ]);
    }

    public static function getRecentImports($cycleId, $limit = 10)
    {
        return self::where('cycle_id', $cycleId)
            ->with(['uploader'])
            ->orderBy('created_at', 'desc')
            ->limit($limit)
            ->get();
    }

    public static function getStatistics($cycleId)
    {
        $imports = self::where('cycle_id', $cycleId)->get();

        return [
            'total_imports' => $imports->count(),
            'completed_imports' => $imports->where('status', 'completed')->count(),
            'failed_imports' => $imports->where('status', 'failed')->count(),
            'total_imported' => $imports->sum('imported_count'),
            'total_failed' => $imports->sum('failed_count'),
        ];
    }
}
