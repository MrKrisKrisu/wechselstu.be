<?php

namespace App\Http\Controllers\Api\Public;

use App\Events\TicketCreated;
use App\Http\Controllers\Controller;
use App\Models\Station;
use App\Repositories\Interfaces\TicketRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MonitorController extends Controller
{
    public function __construct(
        private readonly TicketRepositoryInterface $tickets,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $tickets = $this->tickets->allVisible();
        $stations = Station::orderBy('name')->get();

        return response()->json([
            'tickets' => $tickets->map(fn ($t) => TicketCreated::serializeTicket($t))->values(),
            'stations' => $stations->map(fn ($s) => [
                'id' => $s->id,
                'name' => $s->name,
                'location' => $s->location,
            ])->values(),
        ]);
    }
}
