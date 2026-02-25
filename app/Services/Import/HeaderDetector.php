<?php

namespace App\Services\Import;

use Maatwebsite\Excel\Facades\Excel;
use Maatwebsite\Excel\HeadingRowImport;

class HeaderDetector
{
    /**
     * Detect column headers from the first row of an Excel/CSV file.
     *
     * @param  string  $filePath  Absolute path to the stored file
     * @param  int     $headerRow Row number (1-based) that contains headers
     * @return array<string>
     */
    public function detect(string $filePath, int $headerRow = 1): array
    {
        // Use PhpSpreadsheet directly for header-only read (faster)
        $reader = $this->resolveReader($filePath);
        $spreadsheet = $reader->load($filePath);
        $sheet = $spreadsheet->getActiveSheet();

        $headers = [];
        $highestCol = $sheet->getHighestColumn($headerRow);
        $highestColIndex = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::columnIndexFromString($highestCol);

        for ($col = 1; $col <= $highestColIndex; $col++) {
            $cell = $sheet->getCellByColumnAndRow($col, $headerRow);
            $value = trim((string) $cell->getValue());
            if ($value !== '') {
                $headers[] = $value;
            }
        }

        return $headers;
    }

    /**
     * Get a PhpSpreadsheet reader appropriate for the file extension.
     */
    private function resolveReader(string $filePath): \PhpOffice\PhpSpreadsheet\Reader\IReader
    {
        $ext = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

        return match ($ext) {
            'xlsx'        => new \PhpOffice\PhpSpreadsheet\Reader\Xlsx(),
            'xls'         => new \PhpOffice\PhpSpreadsheet\Reader\Xls(),
            'csv'         => new \PhpOffice\PhpSpreadsheet\Reader\Csv(),
            'ods'         => new \PhpOffice\PhpSpreadsheet\Reader\Ods(),
            default       => new \PhpOffice\PhpSpreadsheet\Reader\Xlsx(),
        };
    }
}
