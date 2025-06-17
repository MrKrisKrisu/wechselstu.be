<?php

namespace App\Http\Controllers;

use App\Enum\WorkOrderStatus;
use App\Enum\WorkOrderType;
use App\Http\Resources\WorkOrderResource;
use App\Models\CashRegister;
use App\Models\ChangeRequestItem;
use App\Models\WorkOrder;
use App\Services\MatrixService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class PublicWorkOrderController extends Controller {

    public function status(string $cashRegisterId): JsonResponse {
        $workOrder = WorkOrder::where('cash_register_id', $cashRegisterId)
                              ->whereIn('status', [WorkOrderStatus::PENDING, WorkOrderStatus::IN_PROGRESS])
                              ->first();
        return response()->json([
                                    'exists' => $workOrder !== null,
                                ]);
    }

    public function store(Request $request, string $cashRegisterId): WorkOrderResource {
        $token = $request->query('token');
        if(!$token) {
            abort(403, __('module.forbidden'));
        }
        $cashRegister = CashRegister::where('id', $cashRegisterId)
                                    ->where('token', $token)
                                    ->firstOrFail();

        // Check for existing work orders
        $existingWorkOrder = $cashRegister->workOrders()
                                          ->whereIn('status', [WorkOrderStatus::PENDING, WorkOrderStatus::IN_PROGRESS])
                                          ->first();

        if($existingWorkOrder) {
            abort(409, 'There is already an active request for this register. Please call 4353 (GELD) via DECT if you need assistance.');
        }

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


        $matrix  = new MatrixService();
        $eventId = $matrix->sendNewMessage($this->getMessageForWorkOrder($workOrder));
        $workOrder->update(['event_id' => $eventId]);

        $workOrder->load('changeRequestItems');

        return new WorkOrderResource($workOrder);
    }

    public static function getMessageForWorkOrder(WorkOrder $workOrder): string {
        $registerName = $workOrder->cashRegister->name ?? $workOrder->cashRegister->id;

        $message = $workOrder->status->getEmoji() . ' - ';
        $message .= match ($workOrder->type) {
            WorkOrderType::CHANGE_REQUEST => self::formatChangeRequestMessage($registerName, $workOrder->changeRequestItems->toArray(), $workOrder->notes),
            WorkOrderType::OVERFLOW       => "Cash overflow reported at register „{$registerName}”." . ($workOrder->notes ? "\n\nNotes: {$workOrder->notes}" : ''),
        };

        if($workOrder->status === WorkOrderStatus::DONE) {
            return "<s>" . $message . "</s>";
        }

        return $message;
    }

    private static function formatChangeRequestMessage(string $registerName, array $items, ?string $notes = null): string {
        $lines = ["Change requested at register „{$registerName}”:"];

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
