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
            'genre_prefs' => 'nullable|array',
            'genre_prefs.*' => 'string|max:50',
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors(),
            ], 422);
        }

        $user = User::create([
            'name' => $request->name,
            'email' => strtolower($request->email),
            'password' => $request->password,
            'role' => User::ROLE_LISTENER,
            'phone' => $request->phone,
            'status' => 'active',
            'ip_address' => $request->ip(),
        ]);

        ListenerProfile::create([
            'user_id' => $user->id,
            'genre_prefs' => $request->input('genre_prefs', []),
            'trust_level' => 0,
        ]);

        $verification->issueCode($user);

        return response()->json([
            'success' => true,
            'message' => 'Account created. A verification code was sent to your email.',
        ], 201);
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

        $token = $user->createToken('listener-mobile')->plainTextToken;

        return response()->json([
            'success' => true,
            'message' => 'Email verified. Welcome!',
            'token' => $token,
            'user' => $this->userPayload($user),
        ]);
    }

    public function login(Request $request)
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

        if ($user->role !== User::ROLE_LISTENER) {
            return response()->json([
                'success' => false,
                'message' => 'This account is not a listener account.',
            ], 403);
        }

        if ($user->status !== 'active') {
            return response()->json([
                'success' => false,
                'message' => 'Your account has been suspended. Contact support for help.',
            ], 403);
        }

        if (! $user->email_verified_at) {
            return response()->json([
                'success' => false,
                'message' => 'Email not verified. Please verify your email first.',
                'requires_verification' => true,
            ], 403);
        }

        $token = $user->createToken('listener-mobile')->plainTextToken;

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
            'listener' => $user->listenerProfile ? [
                'genre_prefs' => $user->listenerProfile->genre_prefs ?? [],
                'trust_level' => $user->listenerProfile->trust_level,
                'streak' => $user->listenerProfile->streak,
                'total_earned' => $user->listenerProfile->total_earned,
            ] : null,
        ];
    }
}