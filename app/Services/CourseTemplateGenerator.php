<?php

namespace App\Services;

use App\Models\DegreeLevel;
use App\Models\Department;
use App\Support\CourseDegreeLevelResolver;
use App\Support\Spreadsheet\XlsxWriter;

class CourseTemplateGenerator
{
    public function build(?Department $department = null, ?DegreeLevel $degreeLevel = null): string
    {
        $levelName = $degreeLevel?->name ?? 'Bachelor';
        $levelNames = $department
            ? CourseDegreeLevelResolver::levelsForDepartment($department)->pluck('name')->all()
            : ['Bachelor', 'Master', 'Diploma', 'Certificate'];

        if ($levelNames === []) {
            $levelNames = [$levelName];
        }

        $coursesSheet = [
            ['Course Code *', 'Course Name *', 'Degree Level *', 'Credits *', 'Status', 'Description'],
            ['ACC101', 'Introduction to Accounting', $levelName, 3, 'active', 'Optional course description'],
            ['ACC102', 'Financial Reporting', $levelName, 4, 'active', ''],
        ];

        $dept = $department?->name ?? '(select department before uploading)';
        $level = $degreeLevel?->name ?? '(select degree level before uploading)';

        $instructions = [
            ['USJ E-Learning — Bulk Course Import Template'],
            [''],
            ['Target department', $dept],
            ['Target degree level', $level],
            [''],
            ['How to use'],
            ['1. Select Program, School, Department, and Degree Level on the Courses page.'],
            ['2. Download this template.'],
            ['3. Fill rows on the "Courses" sheet. Do not change the header row.'],
            ['4. Delete the two sample rows or replace them with your real courses.'],
            ['5. Upload the file using "Upload Excel" on the Courses page.'],
            [''],
            ['Column rules'],
            ['Course Code *', 'Required. Unique per department and degree level. Minimum 2 characters.'],
            ['Course Name *', 'Required. Unique per department and degree level. Minimum 4 characters.'],
            ['Degree Level *', 'Required. Choose from the dropdown (e.g. Bachelor, Master).'],
            ['Credits *', 'Required. Whole number from 1 to 12.'],
            ['Status', 'Choose active or inactive from the dropdown. Defaults to active if blank.'],
            ['Description', 'Optional. Short text description.'],
            [''],
            ['Smart import behaviour'],
            ['• Empty rows are skipped automatically.'],
            ['• Courses are linked to the selected department and degree level.'],
            ['• If a course code already exists for the same department and level, that row is updated.'],
            ['• Duplicate codes inside the same file are rejected.'],
            ['• Supported formats: .xlsx, .csv'],
        ];

        $validations = [
            [
                'column' => 2,
                'fromRow' => 2,
                'toRow' => 1000,
                'list' => $levelNames,
                'promptTitle' => 'Degree level',
                'prompt' => 'Select the education level for this course.',
                'errorTitle' => 'Invalid degree level',
                'error' => 'Please choose a valid degree level from the list.',
            ],
            [
                'column' => 4,
                'fromRow' => 2,
                'toRow' => 1000,
                'list' => ['active', 'inactive'],
                'promptTitle' => 'Course status',
                'prompt' => 'Select active or inactive from the list.',
                'errorTitle' => 'Invalid status',
                'error' => 'Please choose active or inactive.',
            ],
        ];

        $writer = new XlsxWriter();
        $writer->addSheet('Courses', $coursesSheet, [
            'headerRows' => 1,
            'freezePane' => true,
            'autoFilter' => true,
            'numericColumns' => [3],
            'columnWidths' => [
                0 => 16,
                1 => 34,
                2 => 18,
                3 => 12,
                4 => 14,
                5 => 44,
            ],
            'validations' => $validations,
        ]);
        $writer->addSheet('Instructions', $instructions, [
            'columnWidths' => [
                0 => 28,
                1 => 72,
            ],
        ]);

        return $writer->toString();
    }
}
