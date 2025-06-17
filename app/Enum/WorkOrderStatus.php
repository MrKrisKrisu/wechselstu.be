<?php declare(strict_types=1);

namespace App\Enum;

enum WorkOrderStatus: string {
    case PENDING     = 'pending';
    case IN_PROGRESS = 'in_progress';
    case DONE        = 'done';

    public static function getStatusByEmoji(string $emoji): ?self {
        return match ($emoji) {
            self::PENDING->getEmoji()     => self::PENDING,
            self::IN_PROGRESS->getEmoji() => self::IN_PROGRESS,
            self::DONE->getEmoji()        => self::DONE,
            default                       => null,
        };
    }

    public function getEmoji(): string {
        return match ($this) {
            self::PENDING     => '🚨',
            self::IN_PROGRESS => '⏳️',
            self::DONE        => '✅️',
        };
    }
}