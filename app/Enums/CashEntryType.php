<?php

namespace App\Enums;

enum CashEntryType: string
{
    case Opening = 'opening';
    case Deposit = 'deposit';
    case Withdrawal = 'withdrawal';
    case CashDrawerOpen = 'cash_drawer_open';
    case CashDrawerClose = 'cash_drawer_close';
    case TransferIn = 'transfer_in';
    case TransferOut = 'transfer_out';
    case Reversal = 'reversal';

    public function label(): string
    {
        return match ($this) {
            self::Opening => 'Anfangsbestand',
            self::Deposit => 'Einzahlung',
            self::Withdrawal => 'Auszahlung',
            self::CashDrawerOpen => 'Kassenöffnung',
            self::CashDrawerClose => 'Kassenschließung',
            self::TransferIn => 'Abschöpfung',
            self::TransferOut => 'Wechselgeld ausgegeben',
            self::Reversal => 'Stornierung',
        };
    }

    public function isCredit(): bool
    {
        return match ($this) {
            self::Opening, self::Deposit, self::TransferIn, self::CashDrawerClose => true,
            self::Withdrawal, self::TransferOut, self::CashDrawerOpen, self::Reversal => false,
        };
    }
}
