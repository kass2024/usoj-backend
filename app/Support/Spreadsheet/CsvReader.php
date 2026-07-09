<?php

namespace App\Support\Spreadsheet;

use RuntimeException;
use ZipArchive;

class CsvReader
{
    /** @return array<int, array<int, string>> */
    public static function read(string $path): array
    {
        $handle = fopen($path, 'rb');
        if ($handle === false) {
            throw new RuntimeException('Could not open CSV file.');
        }

        $rows = [];
        $firstLine = fgets($handle);
        if ($firstLine === false) {
            fclose($handle);

            return [];
        }

        $delimiter = self::detectDelimiter($firstLine);
        rewind($handle);

        while (($data = fgetcsv($handle, 0, $delimiter)) !== false) {
            if ($data === [null] || $data === false) {
                continue;
            }
            $rows[] = array_map(static fn ($v) => is_string($v) ? trim($v) : (string) $v, $data);
        }

        fclose($handle);

        return $rows;
    }

    protected static function detectDelimiter(string $line): string
    {
        $candidates = [',', ';', "\t"];
        $best = ',';
        $bestCount = -1;

        foreach ($candidates as $delimiter) {
            $count = substr_count($line, $delimiter);
            if ($count > $bestCount) {
                $bestCount = $count;
                $best = $delimiter;
            }
        }

        return $best;
    }
}
