<?php

namespace App\Services\Import;

use App\Models\Capex;
use App\Models\CapexItem;
use App\Models\Department;
use App\Models\ImportHistory;
use App\Models\ImportLog;
use PhpOffice\PhpSpreadsheet\IOFactory;

/**
 * Specialized import service for CapEx items.
 * Handles mapping from Excel to capex_items, auto-creating Capex header per dept/year,
 * and reading the ID CapEx directly from Excel (not auto-generating on import).
 */
class CapexImportService
{
    /**
     * Import all CapEx items from an Excel file for ALL units (admin use).
     * Reads "ID Unit" column to resolve department, then finds/creates Capex header.
     *
     * @param  ImportHistory  $history
     * @param  array          $config  ['column_map' => [...], 'fiscal_year' => int, 'mode' => add|replace|upsert]
     */
    public function executeAll(ImportHistory $history, array $config): void
    {
        $this->runImport($history, $config, null);
    }

    /**
     * Import CapEx items for a specific unit/department.
     *
     * @param  ImportHistory  $history
     * @param  array          $config  ['column_map' => [...], 'fiscal_year' => int, 'mode' => add|replace|upsert]
     * @param  int            $departmentId  The department to import for
     */
    public function executeForDept(ImportHistory $history, array $config, int $departmentId): void
    {
        $this->runImport($history, $config, $departmentId);
    }

    private function runImport(ImportHistory $history, array $config, ?int $fixedDepartmentId): void
    {
        $columnMap  = $config['column_map'] ?? [];
        $fiscalYear = (int) ($config['fiscal_year'] ?? date('Y'));
        $mode       = $config['mode'] ?? 'add';
        $batchSize  = config('import-engine.default_batch_size', 500);
        $enableLog  = config('import-engine.enable_logging', true);
        // For per-unit: filter rows by this dept code (uppercase), skip non-matching rows
        $filterDeptCode = ($fixedDepartmentId !== null)
            ? strtoupper(trim($config['dept_code'] ?? ''))
            : null;

        $history->update(['status' => 'processing', 'started_at' => now()]);

        try {
            $filePath    = \Illuminate\Support\Facades\Storage::path($history->filename);
            $spreadsheet = IOFactory::load($filePath);
            $sheet       = $spreadsheet->getActiveSheet();
            $highestRow  = $sheet->getHighestRow();
            $highestCol  = $sheet->getHighestColumn();
            $highestColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestCol);

            // Read headers from row 1
            $excelHeaders = [];
            for ($col = 1; $col <= $highestColIndex; $col++) {
                $excelHeaders[$col] = trim((string) $sheet->getCellByColumnAndRow($col, 1)->getValue());
            }

            // Pre-cache departments by code
            $deptByCode = Department::all()->keyBy(fn($d) => strtoupper(trim($d->code)));

            $totalRows   = $highestRow - 1; // exclude header
            $successRows = 0;
            $failedRows  = 0;

            for ($row = 2; $row <= $highestRow; $row++) {
                $rawRow = [];
                for ($col = 1; $col <= $highestColIndex; $col++) {
                    $header = $excelHeaders[$col] ?? "col_{$col}";
                    $rawRow[$header] = $sheet->getCellByColumnAndRow($col, $row)->getFormattedValue();
                }

                // Skip empty rows
                if (empty(array_filter($rawRow, fn($v) => $v !== '' && $v !== null))) {
                    $totalRows--;
                    continue;
                }

                // Map Excel columns via column_map
                $mapped = $this->mapRow($rawRow, $columnMap);

                // Per-unit: skip rows whose unit_code doesn't match this dept
                if ($filterDeptCode !== null && isset($columnMap['unit_code'])) {
                    $rowUnitCode = strtoupper(trim($mapped['unit_code'] ?? ''));
                    if ($rowUnitCode !== $filterDeptCode) {
                        $totalRows--; // don't count skipped rows
                        continue;
                    }
                }

                try {
                    // Resolve department
                    if ($fixedDepartmentId !== null) {
                        $departmentId = $fixedDepartmentId;
                    } else {
                        // Read from Excel column mapped as "unit_code"
                        $unitCode = strtoupper(trim($mapped['unit_code'] ?? ''));
                        $dept     = $deptByCode[$unitCode] ?? null;
                        if (!$dept) {
                            throw new \Exception("Kode unit '{$unitCode}' tidak ditemukan di database.");
                        }
                        $departmentId = $dept->id;
                    }

                    // Get or create Capex header for this dept + year
                    $capex = Capex::firstOrCreate(
                        ['department_id' => $departmentId, 'fiscal_year' => $fiscalYear],
                        ['status' => 'active', 'created_by' => $history->imported_by]
                    );

                    // Prepare item data
                    $itemData = [
                        'capex_id'        => $capex->id,
                        'capex_id_number' => trim($mapped['capex_id_number'] ?? ''),
                        'item_name'       => trim($mapped['item_name'] ?? ''),
                        'description'     => $mapped['description'] ?? null,
                        'category'        => $mapped['category'] ?? null,
                        'capex_type'      => $mapped['capex_type'] ?? null,
                        'priority_scale'  => isset($mapped['priority_scale']) ? (int) $mapped['priority_scale'] : null,
                        'month'           => $mapped['month'] ?? null,
                        'amount_per_year' => isset($mapped['amount_per_year']) ? $this->parseAmount($mapped['amount_per_year']) : null,
                        'budget_amount'   => $this->parseAmount($mapped['budget_amount'] ?? 0),
                        'pic'             => $mapped['pic'] ?? null,
                        'used_amount'     => 0,
                        'status'          => 'available',
                    ];

                    if (empty($itemData['capex_id_number'])) {
                        throw new \Exception('ID CapEx tidak boleh kosong.');
                    }
                    if (empty($itemData['item_name'])) {
                        throw new \Exception('Nama item tidak boleh kosong.');
                    }

                    $this->persist($itemData, $mode);
                    $successRows++;

                } catch (\Throwable $e) {
                    $failedRows++;
                    if ($enableLog) {
                        ImportLog::create([
                            'history_id' => $history->id,
                            'row_number' => $row,
                            'row_data'   => $rawRow,
                            'errors'     => ['import' => [$e->getMessage()]],
                        ]);
                    }
                }

                // Update progress periodically
                if (($row - 1) % $batchSize === 0 || $row === $highestRow) {
                    $history->update([
                        'total_rows'   => $totalRows,
                        'success_rows' => $successRows,
                        'failed_rows'  => $failedRows,
                    ]);
                }
            }

            $history->update([
                'total_rows'   => $totalRows,
                'success_rows' => $successRows,
                'failed_rows'  => $failedRows,
                'status'       => 'done',
                'finished_at'  => now(),
            ]);

        } catch (\Throwable $e) {
            $history->update(['status' => 'failed', 'finished_at' => now()]);
            throw $e;
        }
    }

    /**
     * Map raw Excel row to item keys via column_map.
     * column_map format (from form): ['system_field_key' => 'Excel Header Name']
     */
    private function mapRow(array $rawRow, array $columnMap): array
    {
        $mapped = [];
        foreach ($columnMap as $systemKey => $excelHeader) {
            if ($excelHeader === '' || $excelHeader === null) continue;
            $mapped[$systemKey] = $rawRow[$excelHeader] ?? null;
        }
        return $mapped;
    }

    private function parseAmount(mixed $value): float
    {
        if (is_numeric($value)) return (float) $value;

        // Strip everything except digits, dot, comma, minus
        $str = preg_replace('/[^\d.,\-]/', '', trim((string) $value));
        if ($str === '' || $str === '-') return 0.0;

        $lastDot   = strrpos($str, '.');
        $lastComma = strrpos($str, ',');

        if ($lastDot !== false && $lastComma !== false) {
            // Both separators present — the one that appears last is the decimal separator
            if ($lastDot > $lastComma) {
                // US format: 4,200,000.00 → remove commas, keep dot
                $str = str_replace(',', '', $str);
            } else {
                // ID format: 4.200.000,00 → remove dots, replace comma with dot
                $str = str_replace('.', '', $str);
                $str = str_replace(',', '.', $str);
            }
        } elseif ($lastComma !== false) {
            // Only comma — check if it's a decimal or thousand separator
            $afterComma = substr($str, $lastComma + 1);
            if (strlen($afterComma) === 3) {
                // Likely thousand separator: e.g. "1,000" or "1,200,000"
                $str = str_replace(',', '', $str);
            } else {
                // Decimal separator: e.g. "1,5"
                $str = str_replace(',', '.', $str);
            }
        }
        // If only dots remain — already valid float (or multiple dots as thousand seps)
        // Remove any remaining leftover thousand dots (e.g. "1.200.000")
        $dotCount = substr_count($str, '.');
        if ($dotCount > 1) {
            // Multiple dots means they are thousand separators — remove all
            $str = str_replace('.', '', $str);
        }

        return (float) $str;
    }

    private function persist(array $itemData, string $mode): void
    {
        $idNumber = $itemData['capex_id_number'];

        switch ($mode) {
            case 'replace':
                // Delete existing with same capex_id_number, then insert
                CapexItem::where('capex_id_number', $idNumber)->delete();
                CapexItem::create($itemData);
                break;
            case 'upsert':
                // Update if exists, insert if not
                CapexItem::updateOrCreate(
                    ['capex_id_number' => $idNumber],
                    $itemData
                );
                break;
            default: // add
                CapexItem::create($itemData);
                break;
        }
    }

    /**
     * Preview rows from file using a column_map. Returns first $limit rows.
     * If $filterDeptCode is set, only rows whose unit_code matches are included.
     */
    public function preview(string $filePath, array $columnMap, int $limit = 25, int $page = 1, string $filterDeptCode = ''): array
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

        $results = [];
        $count = 0;
        $skip = ($page - 1) * $limit;
        $skipped = 0;

        for ($row = 2; $row <= $highestRow && $count < $limit; $row++) {
            $rawRow = [];
            for ($col = 1; $col <= $highestColIndex; $col++) {
                $header = $excelHeaders[$col] ?? "col_{$col}";
                $rawRow[$header] = $sheet->getCellByColumnAndRow($col, $row)->getFormattedValue();
            }

            if (empty(array_filter($rawRow, fn($v) => $v !== '' && $v !== null))) continue;

            $mapped = $this->mapRow($rawRow, $columnMap);

            // Filter by unit_code for per-unit preview
            if ($filterDeptCode !== '' && isset($columnMap['unit_code'])) {
                $rowUnitCode = strtoupper(trim($mapped['unit_code'] ?? ''));
                if ($rowUnitCode !== strtoupper($filterDeptCode)) continue;
            }

            if ($skipped < $skip) { $skipped++; continue; }

            $idNumber = trim($mapped['capex_id_number'] ?? '');
            $exists = $idNumber ? CapexItem::where('capex_id_number', $idNumber)->exists() : false;

            $results[] = [
                'row'    => $row,
                'raw'    => $rawRow,
                'mapped' => $mapped,
                'valid'  => !empty($mapped['capex_id_number']) && !empty($mapped['item_name']),
                'errors' => array_filter([
                    'capex_id_number' => empty($mapped['capex_id_number']) ? ['ID CapEx wajib diisi'] : [],
                    'item_name'       => empty($mapped['item_name']) ? ['Nama item wajib diisi'] : [],
                ]),
                'exists' => $exists,
            ];

            $count++;
        }

        return $results;
    }

    public function countRows(string $filePath): int
    {
        $spreadsheet = IOFactory::load($filePath);
        $sheet = $spreadsheet->getActiveSheet();
        $highestRow = $sheet->getHighestRow();
        $highestCol = $sheet->getHighestColumn();
        $highestColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestCol);

        $count = 0;
        for ($row = 2; $row <= $highestRow; $row++) {
            for ($col = 1; $col <= $highestColIndex; $col++) {
                $v = $sheet->getCellByColumnAndRow($col, $row)->getFormattedValue();
                if ($v !== '' && $v !== null) { $count++; break; }
            }
        }
        return $count;
    }
}
