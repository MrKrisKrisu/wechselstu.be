<?php

namespace App\Observers;

use App\Http\Controllers\PublicWorkOrderController;
use App\Models\WorkOrder;
use App\Services\MatrixService;

class WorkOrderObserver {
    public function updated(WorkOrder $workOrder): void {
        if($workOrder->wasChanged('status') && $workOrder->event_id) {
            $matrix = new MatrixService();
            $matrix->updateMessage($workOrder->event_id, PublicWorkOrderController::getMessageForWorkOrder($workOrder));
        }
    }
}
