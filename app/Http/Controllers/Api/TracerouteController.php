<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\TracerouteJob;
use App\Traits\NetworkHelpersTrait;
use Illuminate\Http\Request;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

class TracerouteController extends Controller
{
    use NetworkHelpersTrait;

    public function show(Request $request, $hostname)
    {
        $socketId = request()->header('X-Socket-Id');

        // Push the job to the queue
        TracerouteJob::dispatch($hostname, $socketId, $request->get('ttl', 30));

        return response()->json([
            'status' => 'ok',
            'message' => 'Traceroute request has been queued. Please wait for the results.',
            'socket_id' => $socketId,
        ]);
    }
}
