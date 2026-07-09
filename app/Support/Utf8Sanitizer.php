<?php

namespace App\Support;

class Utf8Sanitizer
{
    public static function clean(?string $value): string
    {
        if ($value === null || $value === '') {
            return '';
        }

        $value = str_replace("\xEF\xBB\xBF", '', $value);

        if (! mb_check_encoding($value, 'UTF-8')) {
            $converted = @iconv('UTF-8', 'UTF-8//IGNORE', $value);
            if ($converted !== false) {
                $value = $converted;
            }
        }

        $value = preg_replace('/[\x00-\x08\x0B\x0C\x0E-\x1F\x7F]/u', '', $value) ?? $value;

        return trim($value);
    }

  /**
   * @param  array<mixed>  $data
   * @return array<mixed>
   */
    public static function cleanArray(array $data): array
    {
        foreach ($data as $key => $value) {
            if (is_string($value)) {
                $data[$key] = self::clean($value);
            } elseif (is_array($value)) {
                $data[$key] = self::cleanArray($value);
            }
        }

        return $data;
    }
}
