<?php

namespace App\Support\Spreadsheet;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use RuntimeException;

class SpreadsheetReader
{
    /** @return Collection<int, array<string, mixed>> */
    public static function rowsFromUpload(UploadedFile $file, ?string $preferredSheet = 'Courses'): Collection
    {
        $extension = strtolower($file->getClientOriginalExtension());
        $path = $file->getRealPath();
        if ($path === false) {
            throw new RuntimeException('Uploaded file is not readable.');
        }

        $rawRows = match ($extension) {
            'csv' => CsvReader::read($path),
            'xlsx' => self::readXlsxRows($path, $preferredSheet),
            'xls' => throw new RuntimeException('Old .xls files are not supported. Please save as .xlsx or .csv in Excel.'),
            default => throw new RuntimeException('Unsupported file type. Use .xlsx or .csv.'),
        };

        if ($rawRows === []) {
            return collect();
        }

        $headers = array_shift($rawRows);
        $headerKeys = array_map(
            static fn ($header) => self::normalizeHeader((string) $header),
            $headers
        );

        return collect($rawRows)->map(function (array $row) use ($headerKeys) {
            $assoc = [];
            foreach ($headerKeys as $index => $key) {
                if ($key === '') {
                    continue;
                }
                $assoc[$key] = $row[$index] ?? null;
            }

            return $assoc;
        })->values();
    }

    /** @return array<int, array<int, string>> */
    protected static function readXlsxRows(string $path, ?string $preferredSheet): array
    {
        $xlsx = XlsxReader::fromFile($path);
        $sheetIndex = 0;

        if ($preferredSheet !== null) {
            $matched = $xlsx->sheetIndexByName($preferredSheet);
            if ($matched !== null) {
                $sheetIndex = $matched;
            }
        }

        return $xlsx->rows($sheetIndex);
    }

    protected static function normalizeHeader(string $header): string
    {
        $header = strtolower(trim($header));
        $header = str_replace('*', '', $header);
        $header = preg_replace('/\s+/', '_', $header) ?? $header;

        return trim($header, '_');
    }
}
