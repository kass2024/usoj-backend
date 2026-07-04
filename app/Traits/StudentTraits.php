<?php

namespace App\Traits;

use App\Models\Modules;
use App\Models\ClassStudent;
use Illuminate\Support\Facades\Auth;


trait StudentTraits
{
    protected function studentClass()
    {
        $class = ClassStudent::where(
            'student_id',
            Auth::guard('student')->user()->id,
        )->latest()->first()->class_year_id;
        return $class;
    }
    // class modules
    protected function classModules()
    {
        $class = $this->studentClass();
        $modules = Modules::where('class_year_id', $class)->pluck('id')->toArray();
        return $modules;
    }

}
