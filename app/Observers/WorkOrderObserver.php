<?php

namespace App\Observers;

use App\Enum\WorkOrderStatus;
use App\Http\Controllers\PublicWorkOrderController;
use App\Models\WorkOrder;
use App\Services\MatrixService;

class WorkOrderObserver {
    public function updated(WorkOrder $workOrder): void {
        if($workOrder->wasChanged('status') && $workOrder->event_id) {
            $matrix = new MatrixService();
            if($workOrder->status === WorkOrderStatus::DONE) {
                $matrix->deleteMessage($workOrder->event_id, 'done');
                return;
            }
            $matrix->updateMessage($workOrder->event_id, PublicWorkOrderController::getMessageForWorkOrder($workOrder));
        }
    }
}
