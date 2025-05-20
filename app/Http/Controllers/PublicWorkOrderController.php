<?php

namespace App\Http\Controllers;

use App\Enum\WorkOrderStatus;
use App\Enum\WorkOrderType;
use App\Http\Resources\WorkOrderResource;
use App\Models\CashRegister;
use App\Models\ChangeRequestItem;
use App\Models\WorkOrder;
use App\Services\MatrixService;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PublicWorkOrderController extends Controller {

    public function store(Request $request, string $cashRegisterId): WorkOrderResource {
        $token = $request->query('token');
        if(!$token) {
            abort(403, __('module.forbidden'));
        }
        $cashRegister = CashRegister::where('id', $cashRegisterId)
                                    ->where('token', $token)
                                    ->firstOrFail();

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

        $message = match ($workOrder->type) {
            WorkOrderType::CHANGE_REQUEST => $this->formatChangeRequestMessage($cashRegister->name ?? $cashRegister->id, $data['items'], $data['notes'] ?? null),
            WorkOrderType::OVERFLOW       => "💰 Cash overflow reported at register „{$cashRegister->name}”." . ($data['notes'] ? "\n\nNotes: {$data['notes']}" : ''),
        };

        MatrixService::sendMessage($message);

        $workOrder->load('changeRequestItems');

        return new WorkOrderResource($workOrder);
    }

    private function formatChangeRequestMessage(string $registerName, array $items, ?string $notes = null): string {
        $lines = ["🔄 Change requested at register „{$registerName}”:"];

        foreach($items as $item) {
            $euro    = $item['denomination'] >= 100 ? ($item['denomination'] / 100) . ' Euro' : $item['denomination'] . ' Cent';
            $lines[] = "- {$item['quantity']} × {$euro}";
        }

        if($notes) {
            $lines[] = "\nNotes: {$notes}";
        }

        return implode("\n", $lines);
    }
}
