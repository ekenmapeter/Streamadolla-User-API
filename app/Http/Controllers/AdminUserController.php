<?php

namespace App\Http\Controllers;

use App\Models\ArtistProfile;
use App\Models\ListenerProfile;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AdminUserController extends Controller
{
    public function create()
    {
        return view('admin.user-form', ['user' => null]);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:255|unique:users,email',
            'password' => 'required|string|min:8',
            'phone' => 'nullable|string|max:30',
            'role' => 'required|in:listener,artist',
            'status' => 'required|in:active,suspended,banned',
            'genre_prefs' => 'nullable|string|max:500',
            'trust_level' => 'nullable|integer|between:0,3',
            'stage_name' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $user = User::create([
            'name' => $request->name,
            'email' => strtolower($request->email),
            'password' => $request->password,
            'phone' => $request->phone,
            'role' => $request->role,
            'status' => $request->status,
        ]);

        $this->syncProfile($user, $request);

        return redirect()->route('admin.listeners.detail', $user)
            ->with('status', "User '{$user->name}' created.");
    }

    public function edit(User $user)
    {
        abort_unless(in_array($user->role, [User::ROLE_LISTENER, User::ROLE_ARTIST]), 404);

        return view('admin.user-form', ['user' => $user]);
    }

    public function update(Request $request, User $user)
    {
        abort_unless(in_array($user->role, [User::ROLE_LISTENER, User::ROLE_ARTIST]), 404);

        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:100',
            'email' => 'required|email|max:255|unique:users,email,'.$user->id,
            'password' => 'nullable|string|min:8',
            'phone' => 'nullable|string|max:30',
            'role' => 'required|in:listener,artist',
            'status' => 'required|in:active,suspended,banned',
            'genre_prefs' => 'nullable|string|max:500',
            'trust_level' => 'nullable|integer|between:0,3',
            'stage_name' => 'nullable|string|max:100',
        ]);

        if ($validator->fails()) {
            return back()->withErrors($validator)->withInput();
        }

        $data = [
            'name' => $request->name,
            'email' => strtolower($request->email),
            'phone' => $request->phone,
            'role' => $request->role,
            'status' => $request->status,
        ];

        if ($request->filled('password')) {
            $data['password'] = $request->password;
        }

        $user->update($data);
        $this->syncProfile($user->fresh(), $request);

        return redirect()->route('admin.listeners.detail', $user)
            ->with('status', "User '{$user->name}' updated.");
    }

    public function suspend(User $user)
    {
        abort_unless($user->role !== User::ROLE_ADMIN, 403);
        abort_unless($user->id !== auth()->id(), 403);

        $user->tokens()->delete();
        $user->update(['status' => 'suspended']);

        return back()->with('status', "{$user->name} has been suspended.");
    }

    public function activate(User $user)
    {
        abort_unless($user->role !== User::ROLE_ADMIN, 403);

        $user->update(['status' => 'active']);

        return back()->with('status', "{$user->name} has been reactivated.");
    }

    public function destroy(Request $request, User $user)
    {
        abort_unless($user->role !== User::ROLE_ADMIN, 403);
        abort_unless($user->id !== auth()->id(), 403);

        $user->tokens()->delete();
        $user->update(['status' => 'deleted']);
        $user->delete();

        return redirect()->route('admin.listeners')
            ->with('status', "User '{$user->name}' moved to trash. All history is retained.");
    }

    public function restore(User $user)
    {
        abort_unless($user->role !== User::ROLE_ADMIN, 403);

        $user->restore();
        $user->update(['status' => 'active']);

        return back()->with('status', "{$user->name} has been restored.");
    }

    private function syncProfile(User $user, Request $request): void
    {
        if ($user->isListener()) {
            ListenerProfile::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'genre_prefs' => $this->parseGenres($request->input('genre_prefs')),
                    'trust_level' => (int) $request->input('trust_level', 0),
                ]
            );
        } elseif ($user->isArtist()) {
            ArtistProfile::updateOrCreate(
                ['user_id' => $user->id],
                ['stage_name' => $request->input('stage_name')]
            );
        }
    }

    private function parseGenres(?string $raw): array
    {
        if (! $raw) {
            return [];
        }

        return array_values(array_filter(array_map(
            fn ($g) => strtolower(trim($g)),
            explode(',', $raw)
        )));
    }
}