<?php

namespace App\Http\Middleware;

use App\Enums\TicketType;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class DetectDomainTicketType
{
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();

        $ticketType = match ($host) {
            config('domains.cash_full') ?: '__unset__' => TicketType::CashFull->value,
            config('domains.change_request') ?: '__unset__' => TicketType::ChangeRequest->value,
            config('domains.other') ?: '__unset__' => TicketType::Other->value,
            default => null,
        };

        $request->attributes->set('ticket_type', $ticketType);

        return $next($request);
    }
}
