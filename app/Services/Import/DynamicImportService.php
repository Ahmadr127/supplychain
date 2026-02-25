<?php

namespace App\Services\Import;

use App\Models\ImportHistory;
use App\Models\ImportLog;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;

class DynamicImportService
{
    public function __construct(
        protected ColumnMapper     $mapper,
        protected ValidationEngine $validator,
        protected ResultBuilder    $builder,
    ) {}

    /**
     * Run the full import pipeline for a given ImportHistory record.
     *
     * @param  ImportHistory  $history
     * @param  array          $config   keys: mode, column_map, unique_keys, rules
     * @return void
     */
    public function execute(ImportHistory $history, array $config): void
    {
        $batchSize = config('import-engine.default_batch_size', 500);
        $enableLog = config('import-engine.enable_logging', true);

        $history->update(['status' => 'processing', 'started_at' => now()]);

        try {
            $filePath = Storage::path($history->filename);
            $spreadsheet = IOFactory::load($filePath);
            $sheet = $spreadsheet->getActiveSheet();

            $highestRow = $sheet->getHighestRow();
            $highestCol = $sheet->getHighestColumn();
            $highestColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestCol);

            // Read headers from row 1
            $excelHeaders = [];
            for ($col = 1; $col <= $highestColIndex; $col++) {
                $excelHeaders[$col] = trim((string) $sheet->getCellByColumnAndRow($col, 1)->getValue());
            }

            $columnMap  = $config['column_map'] ?? [];
            $rules      = $config['rules'] ?? [];
            $mode       = $config['mode'] ?? 'add';
            $uniqueKeys = $config['unique_keys'] ?? [];
            $modelClass  = $history->target_model;

            $totalRows   = $highestRow - 1; // excluding header
            $successRows = 0;
            $failedRows  = 0;

            $history->update(['total_rows' => $totalRows]);

            // Process in batches
            $batchStart = 2; // data starts from row 2
            while ($batchStart <= $highestRow) {
                $batchEnd = min($batchStart + $batchSize - 1, $highestRow);

                for ($row = $batchStart; $row <= $batchEnd; $row++) {
                    $rawRow = [];
                    for ($col = 1; $col <= $highestColIndex; $col++) {
                        $header = $excelHeaders[$col] ?? "col_{$col}";
                        $rawRow[$header] = $sheet->getCellByColumnAndRow($col, $row)->getFormattedValue();
                    }

                    // Skip fully empty rows
                    if (empty(array_filter($rawRow, fn($v) => $v !== '' && $v !== null))) {
                        $totalRows--;
                        continue;
                    }

                    // Map → Sanitize → Validate → Persist
                    $mapped    = $this->mapper->map($rawRow, $columnMap);
                    $sanitized = $this->mapper->sanitize($mapped);
                    $validation = $this->validator->validate($sanitized, $rules);

                    if (!$validation['valid']) {
                        $failedRows++;
                        if ($enableLog) {
                            ImportLog::create([
                                'history_id' => $history->id,
                                'row_number' => $row,
                                'row_data'   => $rawRow,
                                'errors'     => $validation['errors'],
                            ]);
                        }
                        continue;
                    }

                    try {
                        $this->builder->persist($modelClass, $sanitized, $mode, $uniqueKeys);
                        $successRows++;
                    } catch (\Throwable $e) {
                        $failedRows++;
                        if ($enableLog) {
                            ImportLog::create([
                                'history_id' => $history->id,
                                'row_number' => $row,
                                'row_data'   => $rawRow,
                                'errors'     => ['db' => [$e->getMessage()]],
                            ]);
                        }
                    }
                }

                // Update progress after each batch
                $history->update([
                    'total_rows'   => $totalRows,
                    'success_rows' => $successRows,
                    'failed_rows'  => $failedRows,
                ]);

                $batchStart = $batchEnd + 1;
            }

            $history->update([
                'status'      => 'done',
                'finished_at' => now(),
            ]);
        } catch (\Throwable $e) {
            $history->update([
                'status'      => 'failed',
                'finished_at' => now(),
            ]);

            throw $e;
        }
    }

    /**
     * Preview the first N data rows from a file using a column_map.
     *
     * @param  string  $filePath    Absolute path to storage file
     * @param  array   $columnMap   ['Excel Header' => 'db_col']
     * @param  array   $rules       Validation rules
     * @param  int     $limit       Number of rows to preview
     * @param  string|null $modelClass  FQCN to check for existing records
     * @param  array   $uniqueKeys  Columns used to detect duplicates
     * @return array   [['row' => int, 'mapped' => [...], 'raw' => [...], 'valid' => bool, 'errors' => [...], 'exists' => bool], ...]
     */
    public function preview(string $filePath, array $columnMap, array $rules, int $limit = 25, ?string $modelClass = null, array $uniqueKeys = [], int $page = 1): array
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestRow();
        $highestCol = $sheet->getHighestColumn();
        $highestColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestCol);

        $excelHeaders = [];
        for ($col = 1; $col <= $highestColIndex; $col++) {
            $excelHeaders[$col] = trim((string) $sheet->getCellByColumnAndRow($col, 1)->getValue());
        }

        $results  = [];
        $count    = 0;
        $skip     = ($page - 1) * $limit;
        $skipped  = 0;

        for ($row = 2; $row <= $highestRow && $count < $limit; $row++) {
            $rawRow = [];
            for ($col = 1; $col <= $highestColIndex; $col++) {
                $header = $excelHeaders[$col] ?? "col_{$col}";
                $rawRow[$header] = $sheet->getCellByColumnAndRow($col, $row)->getFormattedValue();
            }

            if (empty(array_filter($rawRow, fn($v) => $v !== '' && $v !== null))) continue;

            // Skip rows for the current page offset
            if ($skipped < $skip) {
                $skipped++;
                continue;
            }

            $mapped    = $this->mapper->map($rawRow, $columnMap);
            $sanitized = $this->mapper->sanitize($mapped);
            $validation = $this->validator->validate($sanitized, $rules);

            // Check if a matching record already exists in DB
            $exists = false;
            if ($modelClass && !empty($uniqueKeys) && class_exists($modelClass)) {
                $conditions = collect($uniqueKeys)
                    ->mapWithKeys(fn($k) => [$k => $sanitized[$k] ?? null])
                    ->filter(fn($v) => $v !== null)
                    ->toArray();
                if (!empty($conditions)) {
                    $exists = $modelClass::where($conditions)->exists();
                }
            }

            $results[] = [
                'row'    => $row,
                'raw'    => $rawRow,
                'mapped' => $sanitized,
                'valid'  => $validation['valid'],
                'errors' => $validation['errors'],
                'exists' => $exists,
            ];

            $count++;
        }

        return $results;
    }

    /**
     * Count total non-empty data rows in a spreadsheet (excluding header).
     */
    public function countRows(string $filePath): int
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestRow();
        $highestCol = $sheet->getHighestColumn();
        $highestColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestCol);

        $count = 0;
        for ($row = 2; $row <= $highestRow; $row++) {
            $hasValue = false;
            for ($col = 1; $col <= $highestColIndex; $col++) {
                $v = $sheet->getCellByColumnAndRow($col, $row)->getFormattedValue();
                if ($v !== '' && $v !== null) { $hasValue = true; break; }
            }
            if ($hasValue) $count++;
        }

        return $count;
    }
}
