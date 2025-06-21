<?php declare(strict_types=1);

namespace App\Enum;

enum WorkOrderType: string {
    case OVERFLOW       = 'overflow';
    case CHANGE_REQUEST = 'change_request';
    case OTHER          = 'other';
}