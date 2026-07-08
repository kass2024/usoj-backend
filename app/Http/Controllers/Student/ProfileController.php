<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Http\Requests\ProfileUpdateRequest;
use App\Models\Student;
use Illuminate\Support\Facades\Storage;  
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Display the user's profile form.
     */
    public function edit(): View
    {
        return view('student.profile.edit', [
            'student' => auth()->guard('student')->user(),
        ]);
    }

    /**
     * Update the user's profile information.
     */
   public function update(Request $request): RedirectResponse
{
    $student = Student::findOrFail(auth()->guard('student')->user()->id);

    $request->validate([
        'fname' => 'required|string|min:3',
        'lname' => 'required|string|min:3',
        'email' => 'required|email|unique:students,email,' . $student->id,
        'phone' => 'required|unique:students,phone,' . $student->id,
        'profile_img' => 'nullable|image|mimes:jpg,jpeg,png|max:2048', // profile photo validation
    ]);

    try {
        // Handle file upload
        if ($request->hasFile('profile_img')) {
            if ($student->profile_img && Storage::disk('public')->exists($student->profile_img)) {
                Storage::disk('public')->delete($student->profile_img);
            }

            $path = $request->file('profile_img')->store('profile_images', 'public');
            $student->profile_img = $path;
        }

        // Update other fields
        $student->fname = $request->fname;
        $student->lname = $request->lname;
        $student->email = $request->email;
        $student->phone = $request->phone;

        $student->save();

        return Redirect::route('student.profile.edit')->with('status', 'profile-updated');
    } catch (\Throwable $th) {
        return back()->with('error', $th->getMessage());
    }
    
}
    /**
     * Delete the user's account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $request->validateWithBag('userDeletion', [
            'password' => ['required', 'current_password'],
        ]);

        $user = $request->user();

        Auth::logout();

        $user->delete();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return Redirect::to('/');
    }
}
