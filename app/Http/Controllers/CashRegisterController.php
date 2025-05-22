<?php declare(strict_types=1);

namespace App\Http\Controllers;

use App\Models\CashRegister;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class CashRegisterController extends Controller {
    public function index(): JsonResponse {
        return response()->json(CashRegister::all());
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
        $data = $request->validate([
                                       'name' => ['required', 'string', 'max:255'],
                                   ]);

        $register = CashRegister::findOrFail($id);
        $register->update(['name' => $data['name']]);

        return response()->json($register);
    }
}
