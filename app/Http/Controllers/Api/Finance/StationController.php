<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Controller;
use App\Models\Station;
use App\Repositories\StationRepository;
use App\Services\StationSignService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;

class StationController extends Controller
{
    public function __construct(
        private readonly StationRepository $stations,
        private readonly StationSignService $signService,
    ) {}

    public function index(): JsonResponse
    {
        $stations = $this->stations->all();
        $balances = $this->stations->balances();

        return response()->json([
            'stations' => $stations->map(fn ($s) => array_merge(
                $this->serialize($s),
                ['balance_cents' => (int) ($balances[$s->id] ?? 0)],
            ))->values(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'location' => ['required', 'string', 'max:200'],
        ]);

        $station = $this->stations->create($validated);

        return response()->json(['station' => $this->serialize($station)], 201);
    }

    public function show(Station $station): JsonResponse
    {
        return response()->json(['station' => $this->serialize($station)]);
    }

    public function update(Request $request, Station $station): JsonResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'location' => ['required', 'string', 'max:200'],
        ]);

        $station = $this->stations->update($station, $validated);

        return response()->json(['station' => $this->serialize($station)]);
    }

    public function sign(Station $station): Response
    {
        $pdf = $this->signService->generate($station);

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="station-sign-'.$station->token.'.pdf"',
        ]);
    }

    public function destroy(Station $station): JsonResponse
    {
        $this->stations->delete($station);

        return response()->json(null, 204);
    }

    private function serialize(Station $station): array
    {
        return [
            'id' => $station->id,
            'name' => $station->name,
            'location' => $station->location,
            'token' => $station->token,
            'created_at' => $station->created_at->toIso8601String(),
        ];
    }
}
