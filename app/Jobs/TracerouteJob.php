<?php

namespace App\Jobs;

use App\Events\MtrEvent;
use App\Events\TracerouteEvent;
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

class TracerouteJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels,NetworkHelpersTrait;

    private string $cacheKey;

    /**
     * Create a new job instance.
     */
    public function __construct(protected string $hostname, protected string $socketId, protected int $ttl = 30)
    {
        $this->cacheKey = sprintf('traceroute_%s', hash('xxh64', trim($this->hostname)));
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
                $path = $executableFinder->find('traceroute');

                $process = new Process([
                    $path,
                    '-n',
                    $this->hostname,
                ]);

                $process->run();

                // Executes after the command finishes
                if (! $process->isSuccessful()) {
                    throw new ProcessFailedException($process);
                }

                $output = $process->getOutput();

                $lines = array_map('trim', explode("\n", trim($output)));
                array_shift($lines);

                $results = collect($lines);

                return $results->map(function($line, $key) {
                    preg_match('/^(\d+)\s+([\da-fA-F:.]+)\s+(\d+\.\d+ ms|\*)\s+(\d+\.\d+ ms|\*)\s+(\d+\.\d+ ms|\*)?/', $line, $matches);
                    if ($matches) {
                        return [
                            'hop' => $matches[1],
                            'hostname' => $matches[2] ?: null,
                            'ip' => $matches[3],
                            'times' => array_filter([
                                $matches[4] ?? null,
                                $matches[5] ?? null,
                                $matches[6] ?? null,
                            ]),
                        ];
                    }

                    return ['hop' => $key + 1, 'hostname' => null, 'ip' => null, 'times' => []];
                });
            });

            broadcast(new TracerouteEvent(collect(['data' => $results]), $this->socketId));
        } catch (\Throwable $e) {
            // Clear the cache if the traceroute command fails.
            Cache::forget($this->cacheKey);

            broadcast(new TracerouteEvent(collect([
                'message' => 'We were unable to run the traceroute command. Please try again.',
                'error' => $e->getMessage(),
                'trace' => $e->getTrace(),
            ]), $this->socketId));
        }
    }
}
