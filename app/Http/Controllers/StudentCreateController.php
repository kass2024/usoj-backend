<?php

namespace App\Http\Controllers;

use App\Models\Program;
use App\Models\School;
use App\Models\Department;
use App\Models\DegreeLevel;

class StudentCreateController extends Controller
{
    public function index()
    {
        $programs = Program::active()->orderBy('name')->get(['id','name']);
        return view('students.index', compact('programs'));
    }

    public function schoolsByProgram(Program $program)
    {
        try {
            $items = $program->schools()
                ->active()
                ->orderBy('name')
                ->get(['id','name']);

            return response()->json($items);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function departmentsBySchool(School $school)
    {
        try {
            $items = $school->departments()
                ->active()
                ->orderBy('name')
                ->get(['id','name']);

            return response()->json($items);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    /**
     * Degree Levels are tied to Program (degree_levels.program_id)
     * We infer program_id from the Department (either department->program_id OR department->school->program_id)
     */
    public function levelsByDepartment(Department $department)
    {
        try {
            $programId =
                // Case A: direct column on Department (if exists)
                ($department->program_id ?? null)
                // Case B: via School -> Program (common)
                ?? optional(optional($department->school)->program)->id;

            $query = DegreeLevel::query()->active()->orderBy('name');
            if ($programId) {
                $query->where('program_id', $programId);
            }

            $items = $query->get(['id','name']);

            return response()->json($items);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }

    public function studentsByDepartment(Department $department)
    {
        try {
            $degreeLevelId = request('degree_level');

            $q = $department->students()
                ->with(['degreeLevel:id,name'])
                ->orderBy('lname')->orderBy('fname');

            if (!empty($degreeLevelId)) {
                $q->where('degree_level_id', $degreeLevelId);
            }

            $rows = $q->get(['id','reg_number','fname','lname','email','phone','status','degree_level_id','profile_img'])
                ->map(function ($s) {
                    return [
                        'id'         => $s->id,
                        'reg_number' => $s->reg_number,
                        'name'       => trim($s->fname.' '.$s->lname),
                        'email'      => $s->email,
                        'phone'      => $s->phone,
                        'status'     => $s->status,
                        'level'      => optional($s->degreeLevel)->name,
                        'profile_img_url' => \App\Support\StudentPhoto::url($s),
                    ];
                });

            return response()->json($rows);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}
