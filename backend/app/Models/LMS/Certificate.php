<?php

namespace App\Models\LMS;

use App\Models\Course;
use App\Models\LearningPath\LearningPath;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Certificate extends Model
{
    use HasFactory;

    protected $fillable = [
        'certificate_id',
        'employee_id',
        'type',
        'course_id',
        'path_id',
        'title',
        'description',
        'issued_date',
        'expiry_date',
        'pdf_url',
        'metadata',
    ];

    protected $casts = [
        'issued_date' => 'date',
        'expiry_date' => 'date',
        'metadata' => 'array',
    ];

    // Relationships
    public function employee(): BelongsTo
    {
        return $this->belongsTo(User::class, 'employee_id');
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class, 'course_id');
    }

    public function path(): BelongsTo
    {
        return $this->belongsTo(LearningPath::class, 'path_id');
    }

    // Scopes
    public function scopeForCourse($query)
    {
        return $query->where('type', 'course');
    }

    public function scopeForLearningPath($query)
    {
        return $query->where('type', 'learning_path');
    }

    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expiry_date')
                ->orWhere('expiry_date', '>', now());
        });
    }

    public function scopeExpired($query)
    {
        return $query->where('expiry_date', '<=', now());
    }

    public function scopeByEmployee($query, $employeeId)
    {
        return $query->where('employee_id', $employeeId);
    }

    // Helper methods
    public function isCourse(): bool
    {
        return $this->type === 'course';
    }

    public function isLearningPath(): bool
    {
        return $this->type === 'learning_path';
    }

    public function isExpired(): bool
    {
        return $this->expiry_date && $this->expiry_date->isPast();
    }

    public function isActive(): bool
    {
        return ! $this->isExpired();
    }

    public function getDaysUntilExpiry(): ?int
    {
        if (! $this->expiry_date) {
            return null;
        }

        return Carbon::now()->diffInDays($this->expiry_date, false);
    }

    public function getVerificationUrl(): string
    {
        return route('api.lms.certificates.verify', ['certificate_id' => $this->certificate_id]);
    }

    public function generatePdf(): string
    {
        // This would integrate with a PDF generation service
        // For now, return a placeholder URL
        $fileName = "certificate_{$this->certificate_id}.pdf";
        $url = storage_path("app/public/certificates/{$fileName}");

        // Here you would implement actual PDF generation
        // using libraries like TCPDF, DomPDF, or an external service

        return $url;
    }

    public function generateCertificateId(): string
    {
        return strtoupper(Str::random(12));
    }

    protected static function booted()
    {
        static::creating(function ($certificate) {
            if (! $certificate->certificate_id) {
                $certificate->certificate_id = $certificate->generateCertificateId();
            }
        });

        static::created(function ($certificate) {
            // Log certificate event
            LMSEvent::logEvent(
                $certificate->employee_id,
                'certificate_earned',
                $certificate->course_id,
                null,
                $certificate->path_id,
                [
                    'certificate_id' => $certificate->certificate_id,
                    'type' => $certificate->type,
                    'title' => $certificate->title,
                ]
            );
        });
    }

    public static function generateForCourse(int $employeeId, int $courseId): self
    {
        $course = Course::findOrFail($courseId);
        $employee = User::findOrFail($employeeId);

        // Check if certificate already exists
        $existing = static::where('employee_id', $employeeId)
            ->where('course_id', $courseId)
            ->where('type', 'course')
            ->first();

        if ($existing) {
            return $existing;
        }

        // Get course metadata for certificate configuration
        $courseMetadata = $course->metadata ?? [];
        $certificateConfig = $courseMetadata['certificate'] ?? [];

        $certificate = static::create([
            'employee_id' => $employeeId,
            'type' => 'course',
            'course_id' => $courseId,
            'title' => $certificateConfig['title'] ?? "Certificate of Completion - {$course->title}",
            'description' => $certificateConfig['description'] ??
                "This certifies that {$employee->name} has successfully completed the course: {$course->title}",
            'issued_date' => now()->toDateString(),
            'expiry_date' => isset($certificateConfig['validity_months'])
                ? now()->addMonths($certificateConfig['validity_months'])->toDateString()
                : null,
            'metadata' => [
                'course_title' => $course->title,
                'employee_name' => $employee->name,
                'completion_date' => now()->toDateString(),
                'instructor' => $course->instructor ?? 'N/A',
                'duration_hours' => $course->duration_minutes ? round($course->duration_minutes / 60, 1) : null,
            ],
        ]);

        // Generate PDF asynchronously
        // dispatch(new GenerateCertificatePdf($certificate));

        return $certificate;
    }

    public static function generateForLearningPath(int $employeeId, int $pathId): self
    {
        $path = LearningPath::findOrFail($pathId);
        $employee = User::findOrFail($employeeId);

        // Check if certificate already exists
        $existing = static::where('employee_id', $employeeId)
            ->where('path_id', $pathId)
            ->where('type', 'learning_path')
            ->first();

        if ($existing) {
            return $existing;
        }

        // Get path metadata for certificate configuration
        $pathMetadata = $path->metadata ?? [];
        $certificateConfig = $pathMetadata['certificate'] ?? [];

        // Calculate total completion time
        $pathEnrollment = PathEnrollment::where('employee_id', $employeeId)
            ->where('path_id', $pathId)
            ->first();

        $certificate = static::create([
            'employee_id' => $employeeId,
            'type' => 'learning_path',
            'path_id' => $pathId,
            'title' => $certificateConfig['title'] ?? "Certificate of Completion - {$path->title}",
            'description' => $certificateConfig['description'] ??
                "This certifies that {$employee->name} has successfully completed the learning path: {$path->title}",
            'issued_date' => now()->toDateString(),
            'expiry_date' => isset($certificateConfig['validity_months'])
                ? now()->addMonths($certificateConfig['validity_months'])->toDateString()
                : null,
            'metadata' => [
                'path_title' => $path->title,
                'employee_name' => $employee->name,
                'completion_date' => now()->toDateString(),
                'total_courses' => $pathEnrollment?->total_courses ?? 0,
                'completion_days' => $pathEnrollment?->getCompletionTime() ?? null,
                'path_description' => $path->description,
            ],
        ]);

        // Generate PDF asynchronously
        // dispatch(new GenerateCertificatePdf($certificate));

        return $certificate;
    }

    public static function verify(string $certificateId): ?self
    {
        return static::where('certificate_id', $certificateId)->first();
    }

    public function toVerificationArray(): array
    {
        return [
            'certificate_id' => $this->certificate_id,
            'employee_name' => $this->employee->name,
            'title' => $this->title,
            'type' => $this->type,
            'issued_date' => $this->issued_date->format('Y-m-d'),
            'expiry_date' => $this->expiry_date?->format('Y-m-d'),
            'is_valid' => $this->isActive(),
            'issued_by' => 'Projitt HR Management System',
            'verification_url' => $this->getVerificationUrl(),
        ];
    }
}
