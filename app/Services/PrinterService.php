<?php

namespace App\Services;

use App\Enums\TicketType;
use App\Models\Ticket;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class PrinterService
{
    // ESC/POS control bytes
    private const ESC = "\x1B";

    private const GS = "\x1D";

    // Initialization
    private const INIT = self::ESC.'@';

    private const CODEPAGE_WPC1252 = self::ESC.'t'."\x10";

    private const LINE_SPACING_WIDE = self::ESC.'3'."\x30"; // 48/180" (~6.8mm)

    // Alignment
    private const ALIGN_LEFT = self::ESC.'a'."\x00";

    private const ALIGN_CENTER = self::ESC.'a'."\x01";

    // Text size
    private const SIZE_NORMAL = self::GS.'!'."\x00";

    private const SIZE_DOUBLE = self::GS.'!'."\x11"; // double width + double height

    // Bold
    private const BOLD_ON = self::ESC.'E'."\x01";

    private const BOLD_OFF = self::ESC.'E'."\x00";

    // Feed & cut
    private const FEED_4 = self::ESC.'d'."\x04";

    private const CUT_PARTIAL = self::GS.'V'."\x01";

    private const DENOMINATIONS = [
        50 => ['0,50 Rolle', 2000],
        100 => ['1,00 Rolle', 2500],
        200 => ['2,00 Rolle', 5000],
        500 => ['5,00 Schein', 500],
        1000 => ['10,00 Schein', 1000],
    ];

    // denomination_cents => [display label, value per unit in cents]
    private const PRINTER_PORT = 9100;

    private int $cols;

    public function __construct()
    {
        $this->cols = config('printer.cols', 46);
    }

    public function print(Ticket $ticket): void
    {
        if (! $ticket->station) {
            Log::warning('No station assigned to ticket, skipping print job', [
                'ticket_id' => $ticket->id,
            ]);

            return;
        }

        $ip = $ticket->station->printer_ip;

        if (empty($ip)) {
            Log::warning('No printer IP configured for station, skipping print job', [
                'ticket_id' => $ticket->id,
                'station_id' => $ticket->station->id,
            ]);

            return;
        }

        Log::info('Printing ticket via station printer', [
            'ticket_id' => $ticket->id,
            'ticket_type' => $ticket->type->value,
            'station_id' => $ticket->station->id,
            'station_name' => $ticket->station->name,
            'printer_ip' => $ip,
        ]);

        $this->send($this->buildEscPos($ticket), $ip);

        Log::info('Ticket printed successfully', [
            'ticket_id' => $ticket->id,
            'printer_ip' => $ip,
        ]);
    }

    private function send(string $data, string $ip): void
    {
        $bytes = strlen($data);

        Log::debug('Opening TCP connection to printer', [
            'printer_ip' => $ip,
            'printer_port' => self::PRINTER_PORT,
            'payload_bytes' => $bytes,
        ]);

        $socket = @fsockopen($ip, self::PRINTER_PORT, $errno, $errstr, 5);

        if (! $socket) {
            Log::error('Failed to connect to printer', [
                'printer_ip' => $ip,
                'printer_port' => self::PRINTER_PORT,
                'errno' => $errno,
                'errstr' => $errstr,
            ]);

            throw new RuntimeException("Cannot connect to printer at {$ip}:".self::PRINTER_PORT.": {$errstr} ({$errno})");
        }

        try {
            $written = 0;

            while ($written < $bytes) {
                $result = fwrite($socket, substr($data, $written));

                if ($result === false) {
                    throw new RuntimeException('Failed to write to printer socket');
                }

                $written += $result;
            }

            fflush($socket);

            Log::debug('ESC/POS data sent to printer', [
                'printer_ip' => $ip,
                'bytes_sent' => $written,
            ]);
        } finally {
            fclose($socket);
        }
    }

    private function buildEscPos(Ticket $ticket, ?string $printedBy = null): string
    {
        $out = self::INIT.self::CODEPAGE_WPC1252.self::LINE_SPACING_WIDE;

        $out .= $this->header($ticket);
        $out .= $this->ticketType($ticket);
        $out .= $this->stationInfo($ticket);

        if ($ticket->type === TicketType::ChangeRequest) {
            $out .= $this->denominationTable($ticket);
        }

        if ($ticket->type === TicketType::CashFull) {
            $abschoepfFill = str_repeat('_', $this->cols - 17);
            $out .= $this->encode("\nAbgeschöpft: ".$abschoepfFill." EUR\n");
        }

        if ($printedBy !== null) {
            $out .= $this->encode("\nGedruckt von: ".$printedBy."\n");
        }

        $out .= $this->checklist($ticket);

        $out .= "\n\n".self::FEED_4.self::CUT_PARTIAL;

        return $out;
    }

    private function header(Ticket $ticket): string
    {
        $stationLine = $ticket->station
            ? $ticket->station->name.' @ '.$ticket->station->location
            : 'Keine Kasse zugewiesen';

        return self::ALIGN_CENTER
            .self::SIZE_DOUBLE.self::BOLD_ON
            .$this->encode("WECHSELSTU.BE\n")
            .self::BOLD_OFF.self::SIZE_NORMAL
            .self::GS.'!'."\x10"  // double height only
            .$this->encode($stationLine."\n")
            .self::SIZE_NORMAL;
    }

    private function encode(string $text): string
    {
        return iconv('UTF-8', 'Windows-1252//TRANSLIT', $text) ?: $text;
    }

    private function ticketType(Ticket $ticket): string
    {
        $weekday = ['Mo.', 'Di.', 'Mi.', 'Do.', 'Fr.', 'Sa.', 'So.'][$ticket->created_at->dayOfWeekIso - 1];
        $time = $weekday.' '.$ticket->created_at->format('d.m. H:i');
        $label = $ticket->type->label();
        $padding = str_repeat(' ', max(1, $this->cols - strlen($label) - strlen($time)));

        return self::ALIGN_LEFT
            .$this->encode($this->sep('='))
            .self::BOLD_ON.$this->encode($label.$padding.$time."\n").self::BOLD_OFF
            .$this->encode($this->sep('='));
    }

    private function sep(string $char): string
    {
        return str_repeat($char, $this->cols)."\n";
    }

    private function stationInfo(Ticket $ticket): string
    {
        $out = self::ALIGN_LEFT;

        if (! empty($ticket->message)) {
            $out .= $this->encode("\nHinweis: ".$ticket->message."\n");
        }

        return $out;
    }

    private function denominationTable(Ticket $ticket): string
    {
        $gebrachtFill = str_repeat('_', $this->cols - 28);

        $out = $this->encode("\n");
        $out .= $this->encode(str_pad('Art', 14).mb_str_pad('Gewünscht', 14, ' ', STR_PAD_RIGHT, 'UTF-8')."Gebracht\n");
        $out .= $this->encode($this->sep('-'));

        $byDenomination = $ticket->denominations->keyBy('denomination_cents');
        $totalCents = 0;

        foreach (self::DENOMINATIONS as $cents => [$artLabel, $valueCents]) {
            $qty = $byDenomination->get($cents)?->quantity ?? 0;
            $label = str_pad($artLabel, 14);
            $qtyStr = str_pad($qty.'x', 14);
            $out .= $this->encode($label.$qtyStr.$gebrachtFill."\n");
            $out .= $this->encode("\n");
            $totalCents += $valueCents * $qty;
        }

        $total = number_format($totalCents / 100, 2, ',', '');
        $gesamtFill = str_repeat('_', $this->cols - 22);
        $out .= $this->encode($this->sep('-'));
        $out .= $this->encode('Gesamt gewünscht: '.$total." EUR\n");
        $out .= $this->encode("\n");
        $out .= $this->encode('Gesamt gebracht:  '.$gesamtFill." EUR\n");

        return $out;
    }

    private function checklist(Ticket $ticket): string
    {
        $deviceId = $ticket->station?->pretix_device_id
            ? (string) $ticket->station->pretix_device_id
            : '________';

        $belegFill = str_repeat('_', $this->cols - 14);

        return $this->encode($this->sep('-'))
            .$this->encode("[ ] Buchung in pretixPOS\n")
            .$this->encode('    Device-ID: '.$deviceId."\n")
            .$this->encode("\n")
            .$this->encode("[ ] Buchung in Hauptkasse\n")
            .$this->encode('    Beleg-ID: '.$belegFill."\n")
            .$this->encode("\n");
    }

    public function printToIp(Ticket $ticket, string $ip, ?string $printedBy = null): void
    {
        Log::info('Printing ticket to explicit IP', [
            'ticket_id' => $ticket->id,
            'ticket_type' => $ticket->type->value,
            'station_id' => $ticket->station?->id,
            'printer_ip' => $ip,
            'printed_by' => $printedBy,
        ]);

        $this->send($this->buildEscPos($ticket, $printedBy), $ip);

        Log::info('Ticket printed successfully', [
            'ticket_id' => $ticket->id,
            'printer_ip' => $ip,
        ]);
    }
}
