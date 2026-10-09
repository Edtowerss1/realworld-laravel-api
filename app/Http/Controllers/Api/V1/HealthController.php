<?php

namespace App\Http\Controllers\Api\V1;

use Illuminate\Http\JsonResponse;

class HealthController extends Controller
{
    public function __invoke(): JsonResponse
    {
        return response()->json([
            'status' => 'ok',
            'service' => config('app.name'),
        ]);
    }
}
