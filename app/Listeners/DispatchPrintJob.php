<?php

namespace App\Listeners;

use App\Events\TicketCreated;
use App\Jobs\PrintTicketJob;

class DispatchPrintJob
{
    public function handle(TicketCreated $event): void
    {
        PrintTicketJob::dispatch($event->ticket);
    }
}
