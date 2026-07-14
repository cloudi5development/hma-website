<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Api\Controller;

/**
 * GET /api/v1/health — a lightweight liveness probe that confirms the API is
 * reachable and returns basic runtime info. Useful for uptime monitors and as
 * a reference for the standard ApiResponse envelope.
 */
class HealthController extends Controller
{
    public function __invoke()
    {
        return $this->success([
            'app' => config('app.name'),
            'environment' => config('app.env'),
            'timestamp' => now()->toIso8601String(),
        ], 'API is healthy.');
    }
}
