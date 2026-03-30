<?php

namespace App\Http\Controllers\Api\Finance;

use App\Http\Controllers\Controller;
use App\Http\Resources\StationResource;
use App\Models\Station;
use App\Repositories\StationRepository;
use App\Services\StationSignService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Http\Response;

class StationController extends Controller
{
    public function __construct(
        private readonly StationRepository $stations,
        private readonly StationSignService $signService,
    ) {}

    public function index(): AnonymousResourceCollection
    {
        return StationResource::collection($this->stations->all());
    }

    public function store(Request $request): StationResource
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'location' => ['required', 'string', 'max:200'],
            'printer_ip' => ['nullable', 'string', 'ip'],
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
