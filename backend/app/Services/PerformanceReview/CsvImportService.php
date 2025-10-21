<?php

namespace App\Services\PerformanceReview;

use App\Models\PerformanceReview\ReviewCompetency;
use App\Models\PerformanceReview\ReviewCriteria;
use App\Models\PerformanceReview\ReviewQuestionImport;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class CsvImportService
{
    public function __construct()
    {
        //
    }

    /**
     * Process a CSV import for review questions/criteria
     */
    public function processImport(ReviewQuestionImport $import)
    {
        try {
            $import->markAsProcessing();

            if (! Storage::exists($import->file_path)) {
                throw new \Exception('Import file not found');
            }

            $csvData = $this->readCsvFile($import->file_path);
            $validatedData = $this->validateCsvData($csvData);

            $importedCount = 0;
            $failedCount = 0;
            $log = [];

            DB::transaction(function () use ($import, $validatedData, &$importedCount, &$failedCount, &$log) {
                foreach ($validatedData as $index => $row) {
                    try {
                        $this->createCompetencyAndCriteria($import->cycle_id, $row);
                        $importedCount++;
                    } catch (\Exception $e) {
                        $failedCount++;
                        $log[] = [
                            'message' => 'Row '.($index + 2).': '.$e->getMessage(),
                            'type' => 'error',
                            'timestamp' => now()->toISOString(),
                        ];
                    }
                }
            });

            $import->markAsCompleted($importedCount, $failedCount, $log);

            // Update cycle setup status if criteria were successfully imported
            if ($importedCount > 0) {
                $cycle = $import->cycle;
                if ($cycle->isSetupIncomplete() || $cycle->hasCompetenciesAdded()) {
                    $cycle->updateSetupStatus('criteria_added');
                }
            }

            return [
                'success' => true,
                'imported_count' => $importedCount,
                'failed_count' => $failedCount,
                'log' => $log,
            ];

        } catch (\Exception $e) {
            Log::error('CSV Import failed', [
                'import_id' => $import->id,
                'error' => $e->getMessage(),
            ]);

            $import->markAsFailed([
                [
                    'message' => 'Import failed: '.$e->getMessage(),
                    'type' => 'error',
                    'timestamp' => now()->toISOString(),
                ],
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
            ];
        }
    }

    /**
     * Read and parse CSV file
     */
    protected function readCsvFile($filePath)
    {
        $content = Storage::get($filePath);
        $lines = explode("\n", $content);
        $data = [];

        foreach ($lines as $line) {
            $line = trim($line);
            if (! empty($line)) {
                $data[] = str_getcsv($line);
            }
        }

        return $data;
    }

    /**
     * Validate CSV data structure and content
     */
    protected function validateCsvData($csvData)
    {
        if (empty($csvData)) {
            throw new \Exception('CSV file is empty');
        }

        $header = array_shift($csvData);
        $expectedHeaders = ['competency', 'criteria', 'weight', 'required', 'active'];

        // Normalize headers (lowercase, trim)
        $normalizedHeader = array_map(function ($h) {
            return strtolower(trim($h));
        }, $header);

        // Check if required headers exist
        foreach ($expectedHeaders as $expectedHeader) {
            if (! in_array($expectedHeader, $normalizedHeader)) {
                throw new \Exception("Missing required column: {$expectedHeader}");
            }
        }

        $headerMap = array_flip($normalizedHeader);
        $validatedData = [];

        foreach ($csvData as $index => $row) {
            if (count($row) !== count($header)) {
                throw new \Exception('Row '.($index + 2).' has incorrect number of columns');
            }

            $competency = trim($row[$headerMap['competency']] ?? '');
            $criteria = trim($row[$headerMap['criteria']] ?? '');
            $weight = trim($row[$headerMap['weight']] ?? '');
            $required = trim($row[$headerMap['required']] ?? '');
            $active = trim($row[$headerMap['active']] ?? '');

            if (empty($competency)) {
                throw new \Exception('Row '.($index + 2).': Competency cannot be empty');
            }

            if (empty($criteria)) {
                throw new \Exception('Row '.($index + 2).': Criteria cannot be empty');
            }

            // Validate weight
            if (! empty($weight) && (! is_numeric($weight) || $weight < 0 || $weight > 100)) {
                throw new \Exception('Row '.($index + 2).': Weight must be a number between 0 and 100');
            }

            // Validate boolean fields
            $required = $this->parseBooleanValue($required, true);
            $active = $this->parseBooleanValue($active, true);

            $validatedData[] = [
                'competency' => $competency,
                'criteria' => $criteria,
                'weight' => empty($weight) ? 0 : (float) $weight,
                'required' => $required,
                'active' => $active,
            ];
        }

        return $validatedData;
    }

    /**
     * Create competency and criteria from validated row data
     */
    protected function createCompetencyAndCriteria($cycleId, $rowData)
    {
        // Find or create competency
        $competency = ReviewCompetency::where('cycle_id', $cycleId)
            ->where('name', $rowData['competency'])
            ->first();

        if (! $competency) {
            $sortOrder = ReviewCompetency::where('cycle_id', $cycleId)->max('sort_order') + 10;

            $competency = ReviewCompetency::create([
                'cycle_id' => $cycleId,
                'name' => $rowData['competency'],
                'description' => 'Imported from CSV',
                'sort_order' => $sortOrder,
                'is_active' => true,
            ]);
        }

        // Create criteria
        $sortOrder = ReviewCriteria::where('competency_id', $competency->id)->max('sort_order') + 10;

        ReviewCriteria::create([
            'competency_id' => $competency->id,
            'text' => $rowData['criteria'],
            'weight' => $rowData['weight'],
            'sort_order' => $sortOrder,
            'is_required' => $rowData['required'],
            'is_active' => $rowData['active'],
        ]);

        return true;
    }

    /**
     * Parse boolean values from CSV
     */
    protected function parseBooleanValue($value, $default = false)
    {
        $value = strtolower(trim($value));

        if (empty($value)) {
            return $default;
        }

        $trueValues = ['1', 'true', 'yes', 'y', 'on'];
        $falseValues = ['0', 'false', 'no', 'n', 'off'];

        if (in_array($value, $trueValues)) {
            return true;
        }

        if (in_array($value, $falseValues)) {
            return false;
        }

        return $default;
    }

    /**
     * Generate sample CSV content for download
     */
    public static function generateSampleCsv()
    {
        $headers = ['competency', 'criteria', 'weight', 'required', 'active'];
        $sampleData = [
            ['Leadership', 'Demonstrates clear vision and strategic thinking', '20', 'true', 'true'],
            ['Leadership', 'Motivates and inspires team members', '20', 'true', 'true'],
            ['Leadership', 'Makes effective decisions under pressure', '20', 'true', 'true'],
            ['Communication', 'Communicates clearly and effectively in writing', '25', 'true', 'true'],
            ['Communication', 'Presents ideas confidently and persuasively', '25', 'true', 'true'],
            ['Teamwork', 'Collaborates effectively with diverse team members', '20', 'true', 'true'],
            ['Teamwork', 'Supports team goals over individual interests', '20', 'true', 'true'],
            ['Problem Solving', 'Identifies problems and root causes accurately', '30', 'true', 'true'],
            ['Problem Solving', 'Develops creative and practical solutions', '30', 'false', 'true'],
            ['Technical Skills', 'Demonstrates required technical competencies', '50', 'true', 'true'],
        ];

        $csv = implode(',', $headers)."\n";

        foreach ($sampleData as $row) {
            $csv .= implode(',', array_map(function ($field) {
                return '"'.str_replace('"', '""', $field).'"';
            }, $row))."\n";
        }

        return $csv;
    }

    /**
     * Validate CSV file before processing
     */
    public function validateCsvFile($filePath)
    {
        if (! Storage::exists($filePath)) {
            return [
                'valid' => false,
                'error' => 'File not found',
            ];
        }

        try {
            $csvData = $this->readCsvFile($filePath);
            $this->validateCsvData($csvData);

            return [
                'valid' => true,
                'preview' => array_slice($csvData, 0, 5), // Return first 5 rows for preview
            ];
        } catch (\Exception $e) {
            return [
                'valid' => false,
                'error' => $e->getMessage(),
            ];
        }
    }
}
