<?php

namespace App\Jobs;

use App\Events\MtrEvent;
use App\Events\PingEvent;
use App\Traits\NetworkHelpersTrait;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

class PingJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels, NetworkHelpersTrait;

    private string $cacheKey;

    /**
     * Create a new job instance.
     */
    public function __construct(protected string $hostname, protected string $socketId, protected int $ttl = 30, protected int $count = 10)
    {
        $this->cacheKey = sprintf('ping_%s', hash('xxh64', trim($this->hostname)));
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        // Check if the hostname is an IP address, if so, do nothing, otherwise resolve it.
        if (! $this->validateHostname($this->hostname)) {
            $this->hostname = gethostbyname($this->hostname);
        }

        try {
            $results = Cache::remember($this->cacheKey, now()->addMinutes($this->ttl), function () {
                $executableFinder = new ExecutableFinder();
                $path = $executableFinder->find('ping');

                $process = new Process([
                    $path,
                    '-n',
                    '-c',
                    $this->count,
                    $this->hostname,
                ]);

                $process->run();

                // Executes after the command finishes
                if (! $process->isSuccessful()) {
                    throw new ProcessFailedException($process);
                }

                $output = $process->getOutput();

                $lines = explode("\n", trim($output));

                $results = [
                    'target' => $this->hostname,
                    'ip' => null,
                    'pings' => [],
                    'statistics' => [],
                ];

                // Extracting IP Address
                if (preg_match('/\(([\d.]+)\)/', $lines[0], $matches)) {
                    $results['ip'] = $matches[1];
                }

                // Parsing ping data
                foreach (array_slice($lines, 1, $this->count) as $line) {
                    if (preg_match('/icmp_seq=(\d+)\s+ttl=(\d+)\s+time=([\d.]+ ms)/', $line, $matches)) {
                        $ping = collect([
                            'sequence' => $matches[1],
                            'ttl' => $matches[2],
                            'time' => $matches[3],
                        ]);

                        $results['pings'][] = $ping;
                    }
                }

                // Parsing statistics
                if (preg_match('/(\d+) packets transmitted, (\d+) received, ([\d.]+)% packet loss/', $lines[$this->count + 3], $matches)) {
                    $results['statistics']['transmitted'] = $matches[1];
                    $results['statistics']['received'] = $matches[2];
                    $results['statistics']['packet_loss'] = $matches[3];
                }
                if (preg_match('/rtt min\/avg\/max\/mdev = ([\d.]+\/[\d.]+\/[\d.]+\/[\d.]+ ms)/', $lines[$this->count + 4], $matches)) {
                    $rtt = explode('/', $matches[1]);
                    $results['statistics']['rtt'] = [
                        'min' => $rtt[0],
                        'avg' => $rtt[1],
                        'max' => $rtt[2],
                        'mdev' => $rtt[3],
                    ];
                }

                return collect($results);
            });

            broadcast(new PingEvent($results, $this->socketId));
        } catch (\Throwable $e) {
            // Clear the cache if the MTR command fails.
            Cache::forget($this->cacheKey);

            broadcast(new PingEvent(collect([
                'message' => 'We were unable to run the PING command. Please try again.',
                'error' => $e->getMessage(),
                'trace' => $e->getTrace(),
            ]), $this->socketId));
        }
    }
}
