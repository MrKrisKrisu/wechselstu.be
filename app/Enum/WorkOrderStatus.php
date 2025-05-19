<?php declare(strict_types=1);

namespace App\Enum;

enum WorkOrderStatus: string {
    case PENDING     = 'pending';
    case IN_PROGRESS = 'in_progress';
    case DONE        = 'done';
}