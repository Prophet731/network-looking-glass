<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

class TracerouteController extends Controller
{
    public function show(Request $request, $hostname)
    {
        // Check if the hostname is an IP address, if so, do nothing, otherwise resolve it.
        if (! filter_var($hostname, FILTER_VALIDATE_IP)) {
            $hostname = gethostbyname($hostname);
        }

        $results = cache()->remember(sprintf('tr-%s', hash('xxh128', $hostname)), now()->addMinutes(30),
            function () use (
                $hostname
            ) {
                $executableFinder = new ExecutableFinder();
                $trPath = $executableFinder->find('traceroute');

                $process = new Process([
                    $trPath,
                    $hostname,
                ]);

                $process->run();

                // Executes after the command finishes
                if (! $process->isSuccessful()) {
                    throw new ProcessFailedException($process);
                }

                $output = $process->getOutput();
                $lines = explode("\n", trim($output));
                array_shift($lines);  // Remove the first line as it's just informational

                $result = [];

                foreach ($lines as $line) {
                    preg_match('/(\d+)\s+([\w.-]+)?\s?\(([\d.]+)\)\s+([\d.]+ ms)?\s+([\d.]+ ms)?\s+([\d.]+ ms)?/',
                        $line, $matches);
                    if ($matches) {
                        $hop = [
                            'hop' => $matches[1],
                            'hostname' => $matches[2] ?: null,
                            'ip' => $matches[3],
                            'times' => array_filter([
                                $matches[4] ?? null,
                                $matches[5] ?? null,
                                $matches[6] ?? null,
                            ]),
                        ];
                        $result[] = $hop;
                    } else {
                        // Handle lines that do not match the expected format (e.g., lines with asterisks)
                        $result[] = ['hop' => count($result) + 1, 'hostname' => null, 'ip' => null, 'times' => []];
                    }
                }

                return collect($result);
            });

        return response()->json($results);
    }
}
