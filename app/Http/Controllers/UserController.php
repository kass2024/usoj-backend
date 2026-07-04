<?php

namespace App\Http\Controllers;

use App\Http\Resources\UserResource;
use App\Models\Department;
use App\Models\School;
use App\Models\User;
use Illuminate\Http\Request;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return view('admin.users');
    }
    public function users()
    {
        $collection = User::where('role', 'admin')->orderByDesc('id')->get();
        return UserResource::collection($collection);
    }
    public function heads_of_departments()
    {
        $departments = Department::all();
        return view('admin.heads-of-departments', compact('departments'));
    }
    public function heads_of_departments_list()
    {
        $collection = User::where('role', 'head_of_department')->orderByDesc('id')->get();
        return UserResource::collection($collection);
    }
    public function lectures()
    {
        return view('admin.lectures');
    }
    public function lectures_list()
    {
        $collection = User::where('role', 'lecture')->orderByDesc('id')->get();
        return UserResource::collection($collection);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|min:4',
            'email' => 'required|email|unique:users,email',
            'phone' => 'required|unique:users,phone',
            'role' => 'required',
            'status' => 'required',
            'department_id' => 'required_if:role,head_of_department',
        ]);
        $request->merge(['password' => bcrypt('password')]);
        try {
            User::create($request->all());
            return back()->with('message', 'User Added Succesfully');
        } catch (\Throwable $th) {
            //throw $th;
            return back()->with('error', $th->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(string $id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(string $id)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, string $id)
    {
        $request->validate([
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email,' . $id,
            'phone' => 'required|unique:users,phone,' . $id,
            'status' => 'required',
            'role' => 'required',
            'department_id' => 'required_if:role,head_of_department',
        ]);

        try {
            User::findorfail($id)->update($request->all());
            return back()->with('message', 'User Updated Succesfully');
        } catch (\Throwable $th) {
            //throw $th;
            return back()->with('error', $th->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(string $id)
    {
        try {
            User::findorfail($id)->delete();
            return back()->with('message', 'User Added Succesfully');
        } catch (\Throwable $th) {
            //throw $th;
            return back()->with('error', $th->getMessage());
        }
    }
}
