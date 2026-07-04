<?php

namespace App\Http\Controllers\StudentAuth;

use Illuminate\View\View;
use App\Models\ClassStudent;
use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\RedirectResponse;
use App\Providers\RouteServiceProvider;
use App\Http\Requests\StudentAuth\LoginRequest;

class AuthenticatedSessionController extends Controller
{
    /**
     * Display the login view.
     */
    public function create(): View
    {
        return view('student.auth.login');
    }

    /**
     * Handle an incoming authentication request.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $class = ClassStudent::where(
            'student_id',
            Auth::guard('student')->user()->id,
        )->latest()->first();

        if (!$class) {
            Auth::guard('student')->logout();

            return redirect()->route('student.login')->with(
                'error',
                'You are not assigned to any class yet. Please contact the admin.',
            )
            ;
        }

        $request->session()->regenerate();

        return redirect()->intended(RouteServiceProvider::STUDENT_HOME);
    }

    /**
     * Destroy an authenticated session.
     */
    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('student')->logout();

        $request->session()->invalidate();

        $request->session()->regenerateToken();

        return redirect('/');
    }
}
