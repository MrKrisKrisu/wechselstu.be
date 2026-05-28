<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Controller;
use App\Http\Resources\StationResource;
use App\Models\Station;
use App\Repositories\StationRepository;
use App\Services\PretixCashBalanceService;
use App\Services\StationSignService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class StationController extends Controller
{
    public function __construct(
        private readonly StationRepository $stations,
        private readonly StationSignService $signService,
        private readonly PretixCashBalanceService $cashBalance,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        return StationResource::collection($this->stations->all());
    }

    public function cashBalances(Request $request): JsonResponse
    {
        $cacheKey = 'pretix_cash_balances';

        if ($request->boolean('live')) {
            try {
                $balances = $this->cashBalance->balanceByDevice();
            } catch (\Throwable) {
                return response()->json(['error' => 'Pretix nicht erreichbar.'], 502);
            }

            $cachedAt = now()->toIso8601String();
            Cache::put($cacheKey, ['balances' => $balances, 'cached_at' => $cachedAt], 3600);

            return response()->json(['balances' => $balances, 'cached_at' => $cachedAt]);
        }

        $cached = Cache::get($cacheKey);

        return response()->json([
            'balances' => $cached['balances'] ?? null,
            'cached_at' => $cached['cached_at'] ?? null,
        ]);
    }

    public function store(Request $request): StationResource
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'location' => ['required', 'string', 'max:200'],
            'printer_ip' => ['nullable', 'string', 'ip'],
            'pretix_device_id' => ['nullable', 'integer', 'unique:stations,pretix_device_id'],
        ]);

        return new StationResource($this->stations->create($validated));
    }

    public function show(Station $station): StationResource
    {
        return new StationResource($station);
    }

    public function update(Request $request, Station $station): StationResource
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'location' => ['required', 'string', 'max:200'],
            'printer_ip' => ['nullable', 'string', 'ip'],
            'pretix_device_id' => ['nullable', 'integer', 'unique:stations,pretix_device_id,'.$station->id],
        ]);

        return new StationResource($this->stations->update($station, $validated));
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
}
