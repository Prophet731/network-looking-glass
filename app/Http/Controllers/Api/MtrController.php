<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\Process\Process;

class MtrController extends Controller
{
    public function show(Request $request, string $hostname)
    {
        // Resolve the hostname to an IP address
        $hostname = gethostbyname($hostname);

        $query = cache()->remember(sprintf('mtr-%s', hash('xxh128', $hostname)), now()->addMinutes(60), function () use ($hostname) {
            $process = new Process([
                'mtr',
                '-z',
                '-n',
                '-j',
                $hostname
            ]);

            $process->run(function ($type, $buffer) use (&$output) {
                try {
                    $output = collect(json_decode($buffer, true, 512, JSON_THROW_ON_ERROR)['report']);
                } catch (\JsonException $e) {
                    $output = collect([]);
                }
            });

            return $output;
        });

        return response()->json($query);
    }
}
