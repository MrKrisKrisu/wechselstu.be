<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\Rules\Password;

class UserController extends Controller
{
    public function index(): JsonResponse
    {
        $users = User::orderBy('name')->get(['id', 'name', 'member_token', 'avatar_path']);

        return response()->json([
            'users' => $users->map(fn ($u) => [
                'id' => $u->id,
                'name' => $u->name,
                'member_token' => $u->member_token,
                'avatar_url' => $u->avatar_path ? "/api/members/{$u->member_token}/avatar" : null,
            ])->values(),
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'member_bio' => ['nullable', 'string', 'max:1000'],
            'member_appearance' => ['nullable', 'string', 'max:500'],
            'avatar' => ['nullable', 'image', 'max:5120'],
        ]);

        $updates = [
            'member_bio' => $validated['member_bio'] ?? null,
            'member_appearance' => $validated['member_appearance'] ?? null,
        ];

        if ($request->hasFile('avatar')) {
            $file = $request->file('avatar');
            $path = 'avatars/'.$request->user()->id;
            Storage::disk('local')->put($path, file_get_contents($file->getRealPath()));
            $updates['avatar_path'] = $path;
            $updates['avatar_mime'] = $file->getMimeType();
        }

        $request->user()->update($updates);

        $user = $request->user()->fresh();

        return response()->json([
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'member_token' => $user->member_token,
                'member_bio' => $user->member_bio,
                'member_appearance' => $user->member_appearance,
                'avatar_url' => $user->avatar_path ? "/api/members/{$user->member_token}/avatar" : null,
            ],
        ]);
    }

    public function changePassword(Request $request): JsonResponse
    {
        $request->validate([
            'current_password' => ['required', 'string', 'current_password'],
            'password' => ['required', 'string', Password::default(), 'confirmed'],
        ]);

        $request->user()->update([
            'password' => Hash::make($request->password),
        ]);

        return response()->json(['message' => 'Passwort erfolgreich geändert.']);
    }
}
