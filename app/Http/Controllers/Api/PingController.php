<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

class PingController extends Controller
{
    public function show(Request $request, string $hostname, int $count = 6)
    {
        // Check if the hostname is an IP address, if so, do nothing, otherwise resolve it.
        if (!filter_var($hostname, FILTER_VALIDATE_IP)) {
            $hostname = gethostbyname($hostname);
        }

        $results = cache()->remember(sprintf('ping-%s', hash('xxh128', $hostname)), now()->addMinute(),
            function () use (
                $count,
                $hostname
            ) {
                $executableFinder = new ExecutableFinder();
                $pingPath = $executableFinder->find('ping');

                $process = new Process([$pingPath, '-c', $count, $hostname]);
                $process->run();

                // Executes after the command finishes
                if (!$process->isSuccessful()) {
                    throw new ProcessFailedException($process);
                }

                $output = $process->getOutput();
                $lines = explode("\n", trim($output));

                $result = [
                    'target' => $hostname,
                    'ip' => null,
                    'pings' => [],
                    'statistics' => []
                ];

                // Extracting IP Address
                if (preg_match('/\(([\d.]+)\)/', $lines[0], $matches)) {
                    $result['ip'] = $matches[1];
                }

                // Parsing ping data
                foreach (array_slice($lines, 1, $count) as $line) {
                    if (preg_match('/icmp_seq=(\d+)\s+ttl=(\d+)\s+time=([\d.]+ ms)/', $line, $matches)) {
                        $ping = [
                            'sequence' => $matches[1],
                            'ttl' => $matches[2],
                            'time' => $matches[3]
                        ];
                        $result['pings'][] = $ping;
                    }
                }

                // Parsing statistics
                if (preg_match('/(\d+) packets transmitted, (\d+) received, ([\d.]+)% packet loss/', $lines[$count + 3], $matches)) {
                    $result['statistics']['transmitted'] = $matches[1];
                    $result['statistics']['received'] = $matches[2];
                    $result['statistics']['packet_loss'] = $matches[3];
                }
                if (preg_match('/rtt min\/avg\/max\/mdev = ([\d.]+\/[\d.]+\/[\d.]+\/[\d.]+ ms)/', $lines[$count + 4], $matches)) {
                    $rtt = explode('/', $matches[1]);
                    $result['statistics']['rtt'] = [
                        'min' => $rtt[0],
                        'avg' => $rtt[1],
                        'max' => $rtt[2],
                        'mdev' => $rtt[3],
                    ];
                }

                return collect($result);
            });

        return response()->json($results);
    }
}
