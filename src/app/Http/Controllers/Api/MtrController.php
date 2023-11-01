<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\Process\Process;

class MtrController extends Controller
{
    public function show(Request $request, string $hostname)
    {
        $process = new Process([
            'mtr',
            '-r',
            $hostname
        ]);

        $output = [];

        $process->run(function ($type, $buffer) use (&$output) {
            $lines = explode("\n", trim($buffer));
            foreach ($lines as $line) {
                $output[] = $line;
            }
        });

        return response()->json([
            'output' => $output,
        ]);
    }
}
