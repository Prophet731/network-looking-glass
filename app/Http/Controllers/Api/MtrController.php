<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\MtrJob;

class MtrController extends Controller
{
    public function show(string $hostname): \Illuminate\Http\JsonResponse
    {
        $socketId = request()->header('X-Socket-Id');

        // Launch job to run MTR.
        MtrJob::dispatch($hostname, $socketId);

        return response()->json([
            'status' => 'ok',
            'message' => 'MTR request in progress.',
            'socket_id' => $socketId,
        ]);
    }
}
