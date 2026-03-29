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

    // Layout
    private const SEP_HEAVY = "==============================================\n";

    private const SEP_LIGHT = "----------------------------------------------\n";

    // denomination_cents => [display label, value per unit in cents]
    private const DENOMINATIONS = [
        50 => ['0,50 Rolle', 2000],
        100 => ['1,00 Rolle', 2500],
        200 => ['2,00 Rolle', 5000],
        500 => ['5,00 Schein', 500],
        1000 => ['10,00 Schein', 1000],
    ];

    private const PRINTER_PORT = 9100;

    public function print(Ticket $ticket): void
    {
        $ip = $ticket->station->printer_ip;

        if (empty($ip)) {
            Log::warning('No printer IP configured for station, skipping print job', [
                'ticket_id' => $ticket->id,
                'station_id' => $ticket->station->id,
            ]);

            return;
        }

        $this->send($this->buildEscPos($ticket), $ip);
    }

    private function send(string $data, string $ip): void
    {
        $socket = @fsockopen($ip, self::PRINTER_PORT, $errno, $errstr, 5);

        if (! $socket) {
            throw new RuntimeException("Cannot connect to printer at {$ip}:".self::PRINTER_PORT.": {$errstr} ({$errno})");
        }

        try {
            $length = strlen($data);
            $written = 0;

            while ($written < $length) {
                $result = fwrite($socket, substr($data, $written));

                if ($result === false) {
                    throw new RuntimeException('Failed to write to printer socket');
                }

                $written += $result;
            }

            fflush($socket);
        } finally {
            fclose($socket);
        }
    }

    private function buildEscPos(Ticket $ticket): string
    {
        $out = self::INIT.self::CODEPAGE_WPC1252.self::LINE_SPACING_WIDE;

        $out .= $this->header($ticket);
        $out .= $this->ticketType($ticket);
        $out .= $this->belegLine();
        $out .= $this->stationInfo($ticket);

        if ($ticket->type === TicketType::ChangeRequest) {
            $out .= $this->denominationTable($ticket);
        }

        if ($ticket->type === TicketType::CashFull) {
            $out .= $this->encode("\nAbgeschöpft: _____________________________ EUR\n");
        }

        $out .= "\n\n".self::FEED_4.self::CUT_PARTIAL;

        return $out;
    }

    private function header(Ticket $ticket): string
    {
        $stationLine = $ticket->station->name.' @ '.$ticket->station->location;

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
        $time = $ticket->created_at->format('d.m. H:i');
        $label = $ticket->type->label();
        $padding = str_repeat(' ', max(1, 46 - strlen($label) - strlen($time)));

        return self::ALIGN_LEFT
            .$this->encode(self::SEP_HEAVY)
            .self::BOLD_ON.$this->encode("{$label}{$padding}{$time}\n").self::BOLD_OFF
            .$this->encode(self::SEP_HEAVY);
    }

    private function belegLine(): string
    {
        return $this->encode("\n")
            .$this->encode("Beleg Nr.: ____________________  Kz.: ________\n")
            .$this->encode(self::SEP_HEAVY)
            .$this->encode("\n");
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
        $out = $this->encode("\n");
        $out .= $this->encode(str_pad('Art', 14).mb_str_pad('Gewünscht', 14, ' ', STR_PAD_RIGHT, 'UTF-8')."Gebracht\n");
        $out .= $this->encode(self::SEP_LIGHT);

        $byDenomination = $ticket->denominations->keyBy('denomination_cents');
        $totalCents = 0;

        foreach (self::DENOMINATIONS as $cents => [$artLabel, $valueCents]) {
            $qty = $byDenomination->get($cents)?->quantity ?? 0;
            $label = str_pad($artLabel, 14);
            $qtyStr = str_pad($qty.'x', 14);
            $out .= $this->encode("{$label}{$qtyStr}__________________\n");
            $out .= $this->encode("\n");
            $totalCents += $valueCents * $qty;
        }

        $total = number_format($totalCents / 100, 2, ',', '');
        $out .= $this->encode(self::SEP_LIGHT);
        $out .= $this->encode("Gesamt gewünscht: {$total} EUR\n");
        $out .= $this->encode("\n");
        $out .= $this->encode("Gesamt gebracht:  _______________________ EUR\n");

        return $out;
    }
}
