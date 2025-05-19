<?php

namespace App\Http\Controllers;

use App\Enum\WorkOrderStatus;
use App\Enum\WorkOrderType;
use App\Http\Resources\WorkOrderResource;
use App\Models\CashRegister;
use App\Models\ChangeRequestItem;
use App\Models\WorkOrder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PublicWorkOrderController extends Controller {

    public function store(Request $request, string $token): WorkOrderResource {
        $cashRegister = CashRegister::where('token', $token)->firstOrFail();

        $data = $request->validate([
                                       'type'                 => ['required', Rule::enum(WorkOrderType::class)],
                                       'notes'                => ['nullable', 'string'],
                                       'items'                => ['required_if:type,change_request', 'array'],
                                       'items.*.denomination' => ['required_with:items', 'integer', 'min:1'],
                                       'items.*.quantity'     => ['required_with:items', 'integer', 'min:1'],
                                   ]);

        $workOrder = WorkOrder::create([
                                           'cash_register_id' => $cashRegister->id,
                                           'type'             => $data['type'],
                                           'status'           => WorkOrderStatus::PENDING,
                                           'notes'            => $data['notes'] ?? null,
                                       ]);

        // If change_request, create associated items
        if($workOrder->type === WorkOrderType::CHANGE_REQUEST && !empty($data['items'])) {
            foreach($data['items'] as $item) {
                ChangeRequestItem::create([
                                              'work_order_id' => $workOrder->id,
                                              'denomination'  => $item['denomination'],
                                              'quantity'      => $item['quantity'],
                                          ]);
            }
        }

        $workOrder->load('changeRequestItems');

        return new WorkOrderResource($workOrder);
    }
}
