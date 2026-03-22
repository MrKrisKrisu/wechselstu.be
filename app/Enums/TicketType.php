<?php

namespace App\Enums;

enum TicketType: string
{
    case CashFull = 'cash_full';
    case ChangeRequest = 'change_request';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::CashFull => 'Kasse voll',
            self::ChangeRequest => 'Wechselgeld',
            self::Other => 'Sonstiges',
        };
    }
}
