<?php

namespace App\Jobs;

use App\Models\Ticket;
use App\Services\MatrixService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class SendMatrixNotificationJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [30, 60, 120];

    public function __construct(public readonly Ticket $ticket) {}

    public function handle(MatrixService $matrix): void
    {
        $matrix->notify($this->ticket);
    }

    public function failed(\Throwable $e): void
    {
        Log::error('SendMatrixNotificationJob permanently failed', [
            'ticket_id' => $this->ticket->id,
            'error' => $e->getMessage(),
        ]);
    }
}
