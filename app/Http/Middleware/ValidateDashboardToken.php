<?php

namespace App\Http\Middleware;

use App\Models\DashboardAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ValidateDashboardToken
{
    public function handle(Request $request, Closure $next): Response
    {
        $token = $request->query('token') ?? $request->bearerToken();

        if (! $token) {
            return response()->json(['message' => 'Dashboard token required.'], 403);
        }

        $access = DashboardAccess::where('token', $token)->first();

        if (! $access) {
            return response()->json(['message' => 'Invalid dashboard token.'], 403);
        }

        $request->attributes->set('dashboard_access', $access);

        return $next($request);
    }
}
