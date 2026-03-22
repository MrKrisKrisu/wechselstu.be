<?php

namespace App\Enums;

enum TicketStatus: string
{
    case Open = 'open';
    case Accepted = 'accepted';
    case Done = 'done';

    public function label(): string
    {
        return match ($this) {
            self::Open => 'Offen',
            self::Accepted => 'In Bearbeitung',
            self::Done => 'Erledigt',
        };
    }
}
