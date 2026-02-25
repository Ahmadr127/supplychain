<?php

namespace App\Services\Import;

use Illuminate\Support\Facades\Validator;

class ValidationEngine
{
    /**
     * Validate a single mapped row against given rules.
     *
     * @param  array  $mappedRow   ['db_column' => value, ...]
     * @param  array  $rules       ['db_column' => 'required|string', ...]
     * @return array  ['valid' => bool, 'errors' => ['field' => ['message', ...], ...]]
     */
    public function validate(array $mappedRow, array $rules): array
    {
        if (empty($rules)) {
            return ['valid' => true, 'errors' => []];
        }

        $validator = Validator::make($mappedRow, $rules);

        if ($validator->fails()) {
            return [
                'valid'  => false,
                'errors' => $validator->errors()->toArray(),
            ];
        }

        return ['valid' => true, 'errors' => []];
    }

    /**
     * Validate multiple rows at once and return only failed ones.
     *
     * @param  array  $rows   Array of mapped rows
     * @param  array  $rules
     * @return array  [rowIndex => ['valid', 'errors'], ...]
     */
    public function validateBatch(array $rows, array $rules): array
    {
        $results = [];
        foreach ($rows as $index => $row) {
            $result = $this->validate($row, $rules);
            if (!$result['valid']) {
                $results[$index] = $result;
            }
        }
        return $results;
    }
}
