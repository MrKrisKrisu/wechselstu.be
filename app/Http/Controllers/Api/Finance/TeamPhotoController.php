<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

class TeamPhotoController extends Controller
{
    public function show(): JsonResponse
    {
        return response()->json([
            'photo_url' => DB::table('team_photo')->exists() ? '/api/finance/team-photo/image' : null,
        ]);
    }

    public function image(): Response
    {
        $photo = DB::table('team_photo')->select('photo', 'mime')->first();

        if (! $photo) {
            abort(404);
        }

        return response($photo->photo, 200, [
            'Content-Type' => $photo->mime,
            'Cache-Control' => 'no-cache',
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $request->validate([
            'photo' => ['required', 'image', 'max:10240'],
        ]);

        $file = $request->file('photo');

        DB::table('team_photo')->delete();
        DB::table('team_photo')->insert([
            'photo' => file_get_contents($file->getRealPath()),
            'mime' => $file->getMimeType() ?? 'image/jpeg',
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        return response()->json(['photo_url' => '/api/finance/team-photo/image']);
    }

    public function destroy(): JsonResponse
    {
        DB::table('team_photo')->delete();

        return response()->json(['photo_url' => null]);
    }
}
