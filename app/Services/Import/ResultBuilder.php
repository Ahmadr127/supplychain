<?php

namespace App\Services\Import;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class ResultBuilder
{
    /**
     * Persist a single row to the database based on the import mode.
     *
     * @param  string  $modelClass   FQCN e.g. App\Models\User
     * @param  array   $row          Mapped + sanitized row data
     * @param  string  $mode         'add' | 'replace' | 'upsert'
     * @param  array   $uniqueKeys   Column(s) used as match key for upsert/replace
     * @return bool
     *
     * @throws \Exception
     */
    public function persist(string $modelClass, array $row, string $mode, array $uniqueKeys = []): bool
    {
        /** @var Model $model */
        $model = new $modelClass;

        switch ($mode) {
            case 'add':
                return $this->doAdd($model, $row);

            case 'replace':
                return $this->doReplace($model, $row, $uniqueKeys);

            case 'upsert':
                return $this->doUpsert($model, $row, $uniqueKeys);

            default:
                throw new \InvalidArgumentException("Unknown import mode: {$mode}");
        }
    }

    /**
     * Bulk persist multiple rows using insert for max performance.
     * Only for 'add' mode.
     */
    public function persistBatch(string $modelClass, array $rows, string $mode, array $uniqueKeys = []): array
    {
        $results = ['success' => 0, 'failed' => 0, 'errors' => []];

        foreach ($rows as $index => $row) {
            try {
                $this->persist($modelClass, $row, $mode, $uniqueKeys);
                $results['success']++;
            } catch (\Throwable $e) {
                $results['failed']++;
                $results['errors'][$index] = $e->getMessage();
            }
        }

        return $results;
    }

    // -----------------------------------------------------------------------
    //  Private helpers
    // -----------------------------------------------------------------------

    private function doAdd(Model $model, array $row): bool
    {
        $instance = (clone $model)->fill($row);
        return $instance->save();
    }

    private function doReplace(Model $model, array $row, array $uniqueKeys): bool
    {
        if (!empty($uniqueKeys)) {
            $conditions = collect($uniqueKeys)
                ->mapWithKeys(fn($k) => [$k => $row[$k] ?? null])
                ->filter(fn($v) => $v !== null)
                ->toArray();

            if (!empty($conditions)) {
                $model::where($conditions)->delete();
            }
        }

        $instance = (clone $model)->fill($row);
        return $instance->save();
    }

    private function doUpsert(Model $model, array $row, array $uniqueKeys): bool
    {
        if (empty($uniqueKeys)) {
            return $this->doAdd($model, $row);
        }

        $conditions = collect($uniqueKeys)
            ->mapWithKeys(fn($k) => [$k => $row[$k] ?? null])
            ->filter(fn($v) => $v !== null)
            ->toArray();

        $existing = $model::where($conditions)->first();

        if ($existing) {
            return (bool) $existing->update($row);
        }

        return $this->doAdd($model, $row);
    }
}
