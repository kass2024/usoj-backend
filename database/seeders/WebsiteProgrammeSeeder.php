<?php

namespace Database\Seeders;

use App\Models\WebsiteProgramme;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class WebsiteProgrammeSeeder extends Seeder
{
    public function run(): void
    {
        $programmes = [
            ['name' => 'Bachelor of Arts with Education Secondary (BAES)', 'duration' => '3 Years', 'mode' => 'Fulltime Only', 'category' => 'undergraduate'],
            ['name' => 'Bachelor of Business Administration (BBA)', 'duration' => '3 Years', 'mode' => 'Fulltime & Weekend', 'category' => 'undergraduate'],
            ['name' => 'Bachelor of Education Primary (BEP)', 'duration' => '2 Years', 'mode' => 'In-service / Recess', 'category' => 'undergraduate'],
            ['name' => 'Bachelor of Human Resource Management (BHRM)', 'duration' => '3 Years', 'mode' => 'Fulltime & Weekend', 'category' => 'undergraduate'],
            ['name' => 'Bachelor of Mass Communication and Journalism (BMCJ)', 'duration' => '3 Years', 'mode' => 'Fulltime & Weekend', 'category' => 'undergraduate'],
            ['name' => 'Bachelor of Public Administrative Sciences and Management (BPASM)', 'duration' => '3 Years', 'mode' => 'Fulltime & Weekend', 'category' => 'undergraduate'],
            ['name' => 'Bachelor of Science in Accounting and Finance (BSAF)', 'duration' => '3 Years', 'mode' => 'Fulltime & Weekend', 'category' => 'undergraduate'],
            ['name' => 'Bachelor of Science in Information Technology (BsIT)', 'duration' => '3 Years', 'mode' => 'Fulltime & Weekend', 'category' => 'undergraduate'],
            ['name' => 'Bachelor of Science in Procurement and Logistics Management (BSPLM)', 'duration' => '3 Years', 'mode' => 'Fulltime & Weekend', 'category' => 'undergraduate'],
            ['name' => 'Bachelor of Science with Education Secondary (BSES)', 'duration' => '3 Years', 'mode' => 'Fulltime Only', 'category' => 'undergraduate'],
            ['name' => 'Bachelor of Social Work and Social Transformation (BSWST)', 'duration' => '3 Years', 'mode' => 'Fulltime & Weekend', 'category' => 'undergraduate'],
            ['name' => 'Diploma in Business Administration', 'duration' => '2 Years', 'mode' => 'Fulltime & Weekend', 'category' => 'diploma'],
            ['name' => 'Diploma in Education Primary', 'duration' => '2 Years', 'mode' => 'In-service / Recess', 'category' => 'diploma'],
            ['name' => 'Diploma in Information Technology', 'duration' => '2 Years', 'mode' => 'Fulltime & Weekend', 'category' => 'diploma'],
            ['name' => 'Diploma in Journalism and Mass Communication', 'duration' => '2 Years', 'mode' => 'Fulltime & Weekend', 'category' => 'diploma'],
            ['name' => 'Diploma in Procurement and Logistics Management', 'duration' => '2 Years', 'mode' => 'Fulltime & Weekend', 'category' => 'diploma'],
            ['name' => 'Computer Applications', 'duration' => 'Short Term', 'mode' => 'Flexible', 'category' => 'short_course'],
            ['name' => 'Soft Skills', 'duration' => 'Short Term', 'mode' => 'Flexible', 'category' => 'short_course'],
        ];

        foreach ($programmes as $index => $programme) {
            WebsiteProgramme::updateOrCreate(
                ['slug' => Str::slug($programme['name'])],
                array_merge($programme, [
                    'status' => 'active',
                    'sort_order' => $index + 1,
                ])
            );
        }
    }
}
