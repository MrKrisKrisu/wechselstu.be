<?php

namespace App\Http\Controllers\Api\Public;

use App\Events\TicketCreated;
use App\Http\Controllers\Controller;
use App\Models\Station;
use App\Repositories\Interfaces\TicketRepositoryInterface;
use Illuminate\Http\JsonResponse;

class StationController extends Controller
{
    public function __construct(
        private readonly TicketRepositoryInterface $tickets,
    ) {}

    public function show(string $token): JsonResponse
    {
        $station = Station::where('token', $token)->firstOrFail();
        $tickets = $this->tickets->forStation($station->id);

        return response()->json([
            'station' => [
                'id' => $station->id,
                'name' => $station->name,
                'location' => $station->location,
                'token' => $station->token,
            ],
            'tickets' => $tickets->map(fn ($t) => TicketCreated::serializeTicket($t))->values(),
        ]);
    }
}
