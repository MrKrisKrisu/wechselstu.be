<?php declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enum\WorkOrderStatus;
use App\Http\Resources\WorkOrderResource;
use App\Models\WorkOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Validation\Rule;

class WorkOrderController extends Controller {

    public function index(Request $request): AnonymousResourceCollection {
        $query = WorkOrder::with(['changeRequestItems', 'cashRegister'])
                          ->orderBy('status');

        if($request->has('status')) {
            $request->validate([
                                   'status' => ['nullable', Rule::enum(WorkOrderStatus::class)],
                               ]);

            $query->where('status', $request->input('status'));
        }

        $workOrders = $query->cursorPaginate();

        return WorkOrderResource::collection($workOrders);
    }

    public function count(): JsonResponse {
        $total      = WorkOrder::count();
        $pending    = WorkOrder::where('status', 'pending')->count();
        $inProgress = WorkOrder::where('status', 'in_progress')->count();
        $done       = WorkOrder::where('status', 'done')->count();

        return response()->json([
                                    'total'       => $total,
                                    'pending'     => $pending,
                                    'in_progress' => $inProgress,
                                    'done'        => $done,
                                ]);
    }

    public function update(Request $request, string $id): WorkOrderResource {
        $data = $request->validate([
                                       'status' => 'required|in:pending,in_progress,done',
                                   ]);

        $workOrder = WorkOrder::findOrFail($id);
        $workOrder->update([
                               'status' => $data['status'],
                           ]);

        return new WorkOrderResource($workOrder);
    }
}
