<?php

namespace App\Services;

use App\Enums\TicketType;
use App\Models\Ticket;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use RuntimeException;

/**
 * @todo funktioniert seit dem refactoring nicht mehr; ggf. nochmal neu schreiben (oder es liegt an meinem Drucker)
 */
class PrinterService
{
    private string $printerIp;

    private string $deviceId;

    public function __construct()
    {
        $this->printerIp = config('services.epos.printer_ip', '');
        $this->deviceId = config('services.epos.device_id', 'local_printer');
    }

    public function print(Ticket $ticket): void
    {
        if (empty($this->printerIp)) {
            Log::warning('EPOS printer IP not configured, skipping print job', ['ticket_id' => $ticket->id]);

            return;
        }

        $xml = $this->buildXml($ticket);
        $url = "https://{$this->printerIp}/cgi-bin/epos/service.cgi?devid={$this->deviceId}&timeout=10000";

        $response = Http::timeout(10)
            ->withoutVerifying()
            ->withHeaders([
                'Content-Type' => 'text/xml; charset=utf-8',
                'SOAPAction' => '""',
            ])
            ->send('POST', $url, ['body' => $xml]);

        if (! $response->successful()) {
            throw new RuntimeException("EPOS printer returned HTTP {$response->status()}");
        }
    }

    private function buildXml(Ticket $ticket): string
    {
        $station = $ticket->station;
        $typeLine = e($ticket->type->label());
        $time = $ticket->created_at->format('d.m.Y H:i:s');
        $stationName = e($station->name);
        $stationLocation = e($station->location);

        $contentLines = '';

        if ($ticket->type === TicketType::ChangeRequest && $ticket->denominations->isNotEmpty()) {
            $contentLines .= "<text>&#10;--- Gewuenschte Rollen ---&#10;</text>\n";
            foreach ($ticket->denominations as $denom) {
                $amount = number_format($denom->denomination_cents / 100, 2, ',', '');
                $contentLines .= "<text>{$denom->quantity}x {$amount} EUR&#10;</text>\n";
            }
        }

        if (! empty($ticket->message)) {
            $msg = e($ticket->message);
            $contentLines .= "<text>&#10;Hinweis: {$msg}&#10;</text>\n";
        }

        return <<<XML
<?xml version="1.0" encoding="utf-8"?>
<s:Envelope xmlns:s="http://schemas.xmlsoap.org/soap/envelope/">
  <s:Body>
    <epos-print xmlns="http://www.epson-pos.com/schemas/2011/03/epos-print">
      <text align="center"/>
      <text width="2" height="2" em="true">WECHSELSTU.BE&#10;</text>
      <text align="left"/>
      <text>================================&#10;</text>
      <text em="true">{$typeLine}&#10;</text>
      <text>================================&#10;</text>
      <text>Station:  {$stationName}&#10;</text>
      <text>Standort: {$stationLocation}&#10;</text>
      <text>Zeit:     {$time}&#10;</text>
      {$contentLines}
      <text>&#10;</text>
      <cut/>
    </epos-print>
  </s:Body>
</s:Envelope>
XML;
    }
}
