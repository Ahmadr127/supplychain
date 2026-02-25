<?php

namespace App\Services\Import;

class ColumnMapper
{
    /**
     * Map a raw Excel row (keyed by Excel header) to a DB row (keyed by DB column name).
     *
     * @param  array  $rawRow    Associative array: ['Excel Header' => value, ...]
     * @param  array  $columnMap Associative array: ['Excel Header' => 'db_column', ...]
     * @return array  ['db_column' => value, ...]
     */
    public function map(array $rawRow, array $columnMap): array
    {
        $mapped = [];

        foreach ($columnMap as $excelCol => $dbCol) {
            if (empty($dbCol)) continue; // skip unmapped columns
            $mapped[$dbCol] = $rawRow[$excelCol] ?? null;
        }

        return $mapped;
    }

    /**
     * Apply value transformations (trim strings, null empty values).
     *
     * @param  array  $mappedRow
     * @return array
     */
    public function sanitize(array $mappedRow): array
    {
        return collect($mappedRow)->map(function ($value) {
            if (is_string($value)) {
                $value = trim($value);
                return $value === '' ? null : $value;
            }
            return $value;
        })->toArray();
    }
}
