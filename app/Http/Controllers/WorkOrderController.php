<?php declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enum\WorkOrderStatus;
use App\Http\Resources\WorkOrderResource;
use App\Models\WorkOrder;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class WorkOrderController extends Controller {

    public function index(Request $request): AnonymousResourceCollection {
        $query = WorkOrder::with(['changeRequestItems', 'cashRegister', 'activities'])
                          ->orderBy('status');

        if($request->filled('status')) {
            $statuses = array_filter(
                explode(',', $request->input('status')),
                fn($status) => $status !== ''
            );

            // check every status value
            foreach($statuses as $status) {
                if(!WorkOrderStatus::tryFrom($status)) {
                    abort(422, "Invalid status value: {$status}");
                }
            }

            $query->whereIn('status', $statuses);
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

    public function screen(Request $request): AnonymousResourceCollection|JsonResponse {
        $token = $request->query('token');

        if(empty($token)) {
            return response()->json(['message' => 'Missing token.'], 400);
        }

        $correctToken = config('app.token.screen');
        if($token !== $correctToken) {
            return response()->json(['message' => 'Invalid token.'], 403);
        }

        $workOrders = WorkOrder::with(['cashRegister', 'changeRequestItems'])
                               ->whereIn('status', ['pending', 'in_progress'])
                               ->orderBy('created_at')
                               ->get();

        return WorkOrderResource::collection($workOrders);
    }
}
