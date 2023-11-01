<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class IpController extends Controller
{
    public function __invoke(Request $request): \Illuminate\Http\JsonResponse
    {
        return response()->json([
            'ip' => $request->ip(),
        ]);
    }
}
