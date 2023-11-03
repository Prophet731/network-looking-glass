<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
        // Check if the hostname is an IP address, if so, do nothing, otherwise resolve it.
        if (! $this->validateHostname($hostname)) {
            $hostname = gethostbyname($hostname);
        }

        $results = cache()->remember(sprintf('tr-%s', hash('xxh128', $hostname)), now()->addMinutes(30),
            function () use (
                $hostname
            ) {
                try {
                    $executableFinder = new ExecutableFinder();
                    $trPath = $executableFinder->find('traceroute');

                    $process = new Process([
                        $trPath,
                        '-n',
                        $hostname,
                    ]);

                    $process->run();

                    // Executes after the command finishes
                    if (! $process->isSuccessful()) {
                        throw new ProcessFailedException($process);
                    }

                    $output = $process->getOutput();
                    $lines = array_map('trim', explode("\n", trim($output)));
                    array_shift($lines);  // Remove the first line as it's just informational

                    $result = [];

                    foreach ($lines as $line) {
                        preg_match('/^(\d+)\s+([\da-fA-F:.]+)\s+(\d+\.\d+ ms|\*)\s+(\d+\.\d+ ms|\*)\s+(\d+\.\d+ ms|\*)?/', $line, $matches);
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
                } catch (\Exception $e) {
                    return collect([]);
                }
            });

        return response()->json($results);
    }
}
