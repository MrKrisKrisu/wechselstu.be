<?php declare(strict_types=1);

namespace App\Http\Controllers;

use App\Enum\WorkOrderType;
use App\Http\Resources\RegisterGroupResource;
use App\Models\RegisterGroup;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RegisterGroupController extends Controller {
    public function index(): AnonymousResourceCollection {
        return RegisterGroupResource::collection(RegisterGroup::all());
    }

    public function registers(string $groupId): JsonResponse {
        //TODO: „auth”
        $group     = RegisterGroup::findOrFail($groupId);
        $registers = $group->cashRegisters()
                           ->with('registerGroup')
                           ->get()
                           ->map(function($register) {
                               $lastOverflowOrder = $register->workOrders()
                                                             ->where('type', WorkOrderType::OVERFLOW)
                                                             ->latest()
                                                             ->first();
                               $lastChangeOrder   = $register->workOrders()
                                                             ->where('type', WorkOrderType::CHANGE_REQUEST)
                                                             ->latest()
                                                             ->first();

                               return [
                                   'id'         => $register->id,
                                   'name'       => $register->name,
                                   'token'      => $register->token,
                                   'group'      => [
                                       'id'   => $register->registerGroup->id,
                                       'name' => $register->registerGroup->name,
                                   ],
                                   'last_order' => [
                                       'overflow' => $lastOverflowOrder ? [
                                           'id'         => $lastOverflowOrder->id,
                                           'status'     => $lastOverflowOrder->status?->value,
                                           'created_at' => $lastOverflowOrder->created_at,
                                           'updated_at' => $lastOverflowOrder->updated_at,
                                       ] : null,
                                       'change'   => $lastChangeOrder ? [
                                           'id'         => $lastChangeOrder->id,
                                           'status'     => $lastChangeOrder->status?->value,
                                           'created_at' => $lastChangeOrder->created_at,
                                           'updated_at' => $lastChangeOrder->updated_at,
                                       ] : null,
                                   ],

                               ];
                           });

        return response()->json($registers);
    }
}
