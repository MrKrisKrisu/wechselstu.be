<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Controller;
use App\Models\DashboardAccess;
use App\Repositories\Interfaces\DashboardAccessRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DashboardAccessController extends Controller
{
    public function __construct(
        private readonly DashboardAccessRepositoryInterface $accesses,
    ) {}

    public function index(): JsonResponse
    {
        $accesses = $this->accesses->all();

        return response()->json([
            'accesses' => $accesses->map(fn ($a) => $this->serialize($a))->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'label' => ['required', 'string', 'max:100'],
        ]);

        $access = $this->accesses->create($validated);

        return response()->json(['access' => $this->serialize($access)], 201);
    }

    public function destroy(DashboardAccess $dashboardAccess): JsonResponse
    {
        $this->accesses->delete($dashboardAccess);

        return response()->json(null, 204);
    }

    private function serialize(DashboardAccess $access): array
    {
        return [
            'id' => $access->id,
            'label' => $access->label,
            'token' => $access->token,
            'created_at' => $access->created_at->toIso8601String(),
        ];
    }
}
