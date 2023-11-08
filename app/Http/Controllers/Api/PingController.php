<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\PingJob;
use App\Traits\NetworkHelpersTrait;
use Illuminate\Http\Request;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

class PingController extends Controller
{
    public function show(Request $request, string $hostname): \Illuminate\Http\JsonResponse
    {
        $socketId = request()->header('X-Socket-Id');

        // Push the job to the queue
        PingJob::dispatch($hostname, $socketId, $request->get('ttl', 30), $request->get('count', 10));

        return response()->json([
            'status' => 'ok',
            'message' => 'Ping request has been queued. Please wait for the results.',
            'socket_id' => $socketId,
        ]);
    }
}
