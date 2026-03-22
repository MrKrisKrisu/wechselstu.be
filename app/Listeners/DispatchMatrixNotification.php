<?php

namespace App\Listeners;

use App\Events\TicketCreated;
use App\Jobs\SendMatrixNotificationJob;

class DispatchMatrixNotification
{
    public function handle(TicketCreated $event): void
    {
        SendMatrixNotificationJob::dispatch($event->ticket);
    }
}
