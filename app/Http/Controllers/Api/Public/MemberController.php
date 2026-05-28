<?php

namespace App\Http\Controllers\Api\Public;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;

class MemberController extends Controller
{
    public function show(string $token): JsonResponse
    {
        $user = User::where('member_token', $token)
            ->select(['name', 'member_token', 'member_bio', 'member_appearance', 'avatar_path'])
            ->firstOrFail();

        return response()->json([
            'member' => [
                'name' => $user->name,
                'bio' => $user->member_bio,
                'appearance' => $user->member_appearance,
                'avatar_url' => $user->avatar_path ? "/api/members/{$token}/avatar" : null,
            ],
        ]);
    }

    public function avatar(string $token): Response
    {
        $user = User::where('member_token', $token)
            ->select(['avatar_path', 'avatar_mime'])
            ->whereNotNull('avatar_path')
            ->firstOrFail();

        $file = Storage::disk('local')->get($user->avatar_path);

        abort_if($file === null, 404);

        return response($file, 200, [
            'Content-Type' => $user->avatar_mime ?? 'image/jpeg',
            'Cache-Control' => 'public, max-age=3600',
        ]);
    }
}
