<?php

namespace App\Jobs;

use App\Models\Ticket;
use App\Services\PrinterService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

class PrintTicketJob implements ShouldQueue
{
    use InteractsWithQueue, Queueable, SerializesModels;

    public int $tries = 3;

    public array $backoff = [30, 60, 120];

    public function __construct(
        public readonly Ticket $ticket,
        public readonly ?string $printerIp = null,
        public readonly ?string $printedBy = null,
    ) {}

    public function handle(PrinterService $printer): void
    {
        Log::info('PrintTicketJob started', [
            'ticket_id' => $this->ticket->id,
            'ticket_type' => $this->ticket->type->value,
            'printer_ip' => $this->printerIp ?? $this->ticket->station?->printer_ip,
            'printed_by' => $this->printedBy,
            'attempt' => $this->attempts(),
        ]);

        if ($this->printerIp !== null) {
            $printer->printToIp($this->ticket, $this->printerIp, $this->printedBy);
        } else {
            $printer->print($this->ticket);
        }
    }

    public function failed(\Throwable $e): void
    {
        Log::error('PrintTicketJob permanently failed', [
            'ticket_id' => $this->ticket->id,
            'error' => $e->getMessage(),
        ]);
    }
}
