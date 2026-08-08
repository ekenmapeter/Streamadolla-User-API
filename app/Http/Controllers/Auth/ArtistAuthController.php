<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\ArtistProfile;
use App\Models\User;
use App\Services\EmailVerificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class ArtistAuthController extends Controller
{
    public function showSignup()
    {
        return view('artist.signup');
    }

    public function signup(Request $request, EmailVerificationService $verification)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'stage_name' => 'required|string|max:100',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:30',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $user = User::create([
            'name' => $request->name,
            'email' => strtolower($request->email),
            'password' => $request->password,
            'role' => User::ROLE_ARTIST,
            'phone' => $request->phone,
            'status' => 'active',
        ]);

        ArtistProfile::create([
            'user_id' => $user->id,
            'stage_name' => $request->stage_name,
        ]);

        $verification->issueCode($user);

        return redirect()->route('artist.verify', ['email' => $user->email])
            ->with('status', 'Account created! Enter the 6-digit code sent to your email.');
    }

    public function showVerify(Request $request)
    {
        if ($request->user()?->email_verified_at) {
            return redirect()->route('artist.dashboard');
        }

        return view('artist.verify', [
            'email' => $request->query('email', $request->user()?->email ?? ''),
        ]);
    }

    public function verify(Request $request, EmailVerificationService $verification)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'code' => 'required|string|digits:6',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $user = User::where('email', strtolower($request->email))->firstOrFail();

        if ($user->role !== User::ROLE_ARTIST) {
            abort(403);
        }

        if (! $verification->verify($user, $request->code)) {
            return back()->withErrors(['code' => 'Invalid or expired verification code.'])->withInput();
        }

        Auth::login($user);

        return redirect()->route('artist.dashboard')
            ->with('status', 'Email verified. Welcome aboard!');
    }

    public function showLogin()
    {
        if (Auth::check()) {
            return $this->redirectToDashboard(Auth::user());
        }

        return view('artist.login');
    }

    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $user = User::where('email', strtolower($request->email))->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return back()->withErrors(['email' => 'Invalid credentials.'])->withInput();
        }

        if ($user->role === User::ROLE_ADMIN) {
            Auth::login($user);
            $request->session()->regenerate();

            return redirect()->route('dashboard');
        }

        if ($user->role !== User::ROLE_ARTIST) {
            return back()->withErrors(['email' => 'This account cannot access the artist portal.'])->withInput();
        }

        Auth::login($user);
        $request->session()->regenerate();

        if (! $user->email_verified_at) {
            return redirect()->route('artist.verify', ['email' => $user->email])
                ->with('status', 'Please verify your email to continue.');
        }

        return redirect()->intended(route('artist.dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }

    private function redirectToDashboard(User $user)
    {
        return redirect($user->isAdmin() ? route('dashboard') : route('artist.dashboard'));
    }
}