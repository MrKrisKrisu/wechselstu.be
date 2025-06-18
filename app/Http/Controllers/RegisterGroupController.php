<?php declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Resources\RegisterGroupResource;
use App\Models\RegisterGroup;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class RegisterGroupController extends Controller {
    public function index(): AnonymousResourceCollection {
        return RegisterGroupResource::collection(RegisterGroup::all());
    }
}
