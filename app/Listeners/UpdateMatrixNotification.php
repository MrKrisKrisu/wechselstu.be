<?php

namespace App\Listeners;

use App\Events\TicketStatusUpdated;
use App\Jobs\UpdateMatrixNotificationJob;

class UpdateMatrixNotification
{
    public function handle(TicketStatusUpdated $event): void
    {
        UpdateMatrixNotificationJob::dispatch($event->ticket);
    }
}
