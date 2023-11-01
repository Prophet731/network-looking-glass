<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\Process\Process;

class TracerouteController extends Controller
{
    public function show(Request $request, $hostname)
    {
        $process = new Process([
            'traceroute',
            $hostname
        ]);

        $output = [];

        $process->run(function ($type, $buffer) use (&$output) {
            $output[] = $buffer;
//            return;
//            $lines = explode("\n", trim($buffer));
//            foreach ($lines as $line) {
//                $output[] = $line;
//            }
        });

        return response()->json([
            'output' => $output,
        ]);
    }
}
