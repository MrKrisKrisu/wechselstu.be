<?php

namespace App\Enums;

enum PretixLineType: string
{
    case ChangeStart = 'CHANGE_START';
    case ChangeOut = 'CHANGE_OUT';
    case ChangeIn = 'CHANGE_IN';
    case ChangeDiff = 'CHANGE_DIFF';

    public function label(): string
    {
        return match ($this) {
            self::ChangeStart => 'Kassenöffnung',
            self::ChangeOut => 'Abschöpfung',
            self::ChangeIn => 'Einlage',
            self::ChangeDiff => 'Kassendifferenz',
        };
    }
}
