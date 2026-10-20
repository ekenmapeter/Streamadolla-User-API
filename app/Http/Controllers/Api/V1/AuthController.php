<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\ListenerProfile;
use App\Models\User;
use App\Services\EmailVerificationService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function register(Request $request, EmailVerificationService $verification)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8|confirmed',
            'phone' => 'nullable|string|max:30',
            'role' => 'sometimes|in:' . User::ROLE_LISTENER . ',' . User::ROLE_VENDOR,
            'genre_prefs' => 'nullable|array',
            'genre_prefs.*' => 'string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $role = $request->input('role', User::ROLE_LISTENER);

        $user = User::create([
            'name' => $request->name,
            'email' => strtolower($request->email),
            'password' => $request->password,
            'role' => $role,
            'phone' => $request->phone,
            'status' => 'active',
            'ip_address' => $request->ip(),
        ]);

        if ($role === User::ROLE_LISTENER) {
            ListenerProfile::create([
                'user_id' => $user->id,
                'genre_prefs' => $request->input('genre_prefs', []),
                'trust_level' => 0,
            ]);
        }

        $verification->issueCode($user);
        $mailSent = $verification->mailWasSent();

        if ($mailSent) {
            $verification->setResendCooldown($user);
        }

        return response()->json([
            'success' => true,
            'message' => $mailSent
                ? 'Account created. A verification code was sent to your email.'
                : 'Account created, but we could not send the verification email. Please tap Resend code.',
            'mail_sent' => $mailSent,
            'retry_after' => $mailSent ? EmailVerificationService::RESEND_COOLDOWN_SECONDS : 0,
        ], 201);
    }

    /**
     * Re-issues a verification code, rate-limited per account.
     */
    public function resendVerification(Request $request, EmailVerificationService $verification)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::where('email', strtolower($request->email))->firstOrFail();

        if ($user->email_verified_at) {
            return response()->json([
                'success' => false,
                'message' => 'Email already verified.',
            ], 422);
        }

        $wait = $verification->resendAfter($user);

        if ($wait > 0) {
            return response()->json([
                'success' => false,
                'message' => "Please wait {$wait} seconds before requesting another code.",
                'retry_after' => $wait,
            ], 429);
        }

        $verification->issueCode($user);

        if (! $verification->mailWasSent()) {
            return response()->json([
                'success' => false,
                'message' => 'We could not send the verification email. Please try again later.',
            ], 503);
        }

        $verification->setResendCooldown($user);

        return response()->json([
            'success' => true,
            'message' => 'A new verification code was sent to your email.',
            'retry_after' => EmailVerificationService::RESEND_COOLDOWN_SECONDS,
        ]);
    }

    public function verifyEmail(Request $request, EmailVerificationService $verification)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email|exists:users,email',
            'code' => 'required|string|digits:6',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::where('email', strtolower($request->email))->firstOrFail();

        if ($user->email_verified_at) {
            return response()->json([
                'success' => false,
                'message' => 'Email already verified.',
            ], 422);
        }

        if (! $verification->verify($user, $request->code)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid or expired verification code.',
            ], 422);
        }

        $token = $user->createToken('mobile')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Email verified. Welcome!',
            'token' => $token,
            'user' => $this->userPayload($user),
        ]);
    }

    public function login(Request $request, EmailVerificationService $verification)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|email',
            'password' => 'required|string',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::where('email', strtolower($request->email))->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid credentials.',
            ], 401);
        }

        if ($user->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Your account has been suspended. Contact support for help.',
            ], 403);
        }

        if (! in_array($user->role, [User::ROLE_LISTENER, User::ROLE_VENDOR])) {
            return response()->json([
                'success' => false,
                'message' => 'This account is not a listener or vendor account.',
            ], 403);
        }

        if (! $user->email_verified_at) {
            return response()->json([
                'success' => false,
                'message' => 'Email not verified. Please verify your email first.',
                'requires_verification' => true,
                'retry_after' => $verification->resendAfter($user),
            ], 403);
        }

        $token = $user->createToken('mobile')->plainTextToken;

        $user->update(['ip_address' => $request->ip()]);

        return response()->json([
            'success' => true,
            'token' => $token,
            'user' => $this->userPayload($user),
        ]);
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logged out successfully.',
        ]);
    }

    public function me(Request $request)
    {
        return response()->json([
            'success' => true,
            'user' => $this->userPayload($request->user()),
        ]);
    }

    public function updateMe(Request $request)
    {
        $user = $request->user();

        $validator = Validator::make($request->all(), [
            'name' => 'sometimes|string|max:100',
            'phone' => 'sometimes|nullable|string|max:30',
            'genre_prefs' => 'sometimes|array',
            'genre_prefs.*' => 'string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        if ($request->has('name')) {
            $user->update(['name' => $request->name]);
        }
        if ($request->has('phone')) {
            $user->update(['phone' => $request->phone]);
        }
        if ($request->has('genre_prefs')) {
            $user->listenerProfile()->updateOrCreate(
                ['user_id' => $user->id],
                ['genre_prefs' => $request->genre_prefs]
            );
        }

        return response()->json([
            'success' => true,
            'user' => $this->userPayload($user->fresh()),
        ]);
    }

    private function userPayload(User $user): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'role' => $user->role,
            'email_verified' => (bool) $user->email_verified_at,
            'vendor' => $user->isVendor(),
            'listener' => $user->listenerProfile ? [
                'genre_prefs' => $user->listenerProfile->genre_prefs ?? [],
                'trust_level' => $user->listenerProfile->trust_level,
                'streak' => $user->listenerProfile->streak,
                'total_earned' => $user->listenerProfile->total_earned,
            ] : null,
        ];
    }
}