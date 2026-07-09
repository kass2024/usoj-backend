<?php

namespace App\Support;

class CourseCreditEstimator
{
    public const HEAVY = 6;

    public const LAB = 4;

    public const STANDARD = 3;

    public const LIGHT = 2;

    /**
     * Assign one fixed credit value based on course weight (name/type).
     */
    public static function estimate(string $name, ?string $code = null, ?int $yearIndex = null): int
    {
        $label = strtolower(trim($name));

        if (self::matches($label, [
            'project i', 'project ii', 'project 1', 'project 2', 'project one', 'project two',
            'internship', 'field attachment', 'industrial training',
            'thesis', 'dissertation', 'capstone', 'research project',
        ])) {
            return self::HEAVY;
        }

        if (self::matches($label, [
            'programming', 'laboratory', 'practical', 'development',
            'network administration', 'network security', 'computer networks',
            'database management', 'database systems', 'software engineering',
            'mobile application', 'web programming', 'web design',
            'cyber security', 'information security', 'digital forensics',
            'machine learning', 'data mining', 'cloud computing',
            'linux system', 'operating systems', 'multimedia systems',
            'artificial intelligence', 'big data', 'data communication',
        ])) {
            return self::LAB;
        }

        if (self::matches($label, [
            'communication skills', 'ethics', 'professional conduct',
            'study skills', 'research and study', 'technical writing',
            'seminar in', 'entrepreneurship', 'introduction to business',
        ])) {
            return self::LIGHT;
        }

        if (self::matches($label, [
            'mathematics', 'statistics', 'research methods',
            'project management', 'policy and governance',
        ])) {
            return self::STANDARD;
        }

        if ($yearIndex !== null && $yearIndex >= 3) {
            return self::LAB;
        }

        return self::STANDARD;
    }

    /** @param  array<int, string>  $needles */
    private static function matches(string $label, array $needles): bool
    {
        foreach ($needles as $needle) {
            if (str_contains($label, $needle)) {
                return true;
            }
        }

        return false;
    }
}
