<?php declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\CashRegisterResource;
use App\Models\CashRegister;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;
use Illuminate\Support\Str;

class CashRegisterController extends Controller {
    public function index(): AnonymousResourceCollection {
        return CashRegisterResource::collection(CashRegister::with(['registerGroup'])->get());
    }

    public function store(Request $request): JsonResponse {
        $data = $request->validate([
                                       'name' => ['required', 'string', 'max:255'],
                                   ]);

        $register = CashRegister::create([
                                             'name'  => $data['name'],
                                             'token' => Str::uuid(),
                                         ]);

        return response()->json($register, 201);
    }

    public function resetToken(string $id): JsonResponse {
        $register = CashRegister::findOrFail($id);
        $register->update(['token' => Str::uuid()]);
        return response()->json(['token' => $register->token]);
    }

    public function update(Request $request, string $id): JsonResponse {
        $validated = $request->validate([
                                            'name'              => ['nullable', 'string', 'max:255'],
                                            'register_group_id' => ['nullable', 'exists:register_groups,id'],
                                        ]);

        $register = CashRegister::findOrFail($id);
        $register->update($validated);

        return response()->json($register);
    }
}
