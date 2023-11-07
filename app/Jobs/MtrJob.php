<?php

namespace App\Jobs;

use App\Events\MtrEvent;
use App\Traits\NetworkHelpersTrait;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

class MtrJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, NetworkHelpersTrait, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(protected string $hostname, protected string $socketId)
    {
        //
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
            $results = Cache::remember(sprintf('mtr_%s', hash('xxh64', $this->hostname)), now()->addMinutes(30), function () {
                $executableFinder = new ExecutableFinder();
                $path = $executableFinder->find('mtr');

                $process = new Process([
                    $path,
                    '-n',
                    '-z',
                    '-j',
                    $this->hostname,
                ]);

                $process->setPty(true);

                $process->run();

                // Executes after the command finishes
                if (! $process->isSuccessful()) {
                    throw new ProcessFailedException($process);
                }

                $output = $process->getOutput();

                // Decode the JSON output from MTR and convert it to a collection.
                $output = collect(json_decode($output, true, 512, JSON_THROW_ON_ERROR)['report']);

                // Add the IP address to each hub.
                foreach ($output['hubs'] as $hub) {
                    $hub['ip'] = gethostbyname($hub['host']);
                }

                return $output;
            });

            broadcast(new MtrEvent($results, $this->socketId));
        } catch (\Throwable $e) {
            broadcast(new MtrEvent(collect([
                'error' => $e->getMessage(),
            ]), $this->socketId));
        }
    }
}
