<?php

namespace App\Jobs\Asn;

use App\Traits\NetworkHelpersTrait;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

class IpLookup implements ShouldQueue
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
        if (! $this->validateHostname($this->hostname)) {
            $this->hostname = gethostbyname($this->hostname);
        }

        try {
            $executableFinder = new ExecutableFinder();
            $path = $executableFinder->find('asn');

            $process = new Process([
                $path,
                '-m',
                $this->hostname,
            ]);

            $process->setPty(true);

            $process->run(function ($type, $buffer) {
                if ($type !== Process::ERR) {
                    broadcast(new \App\Events\Asn\IpLookup($buffer, $this->socketId));
                } else {
                    broadcast(new \App\Events\Asn\IpLookup(collect([
                        'error' => $buffer,
                    ]), $this->socketId));
                }
            });
        } catch (\Throwable $e) {
            broadcast(new \App\Events\Asn\IpLookup(collect([
                'error' => $e->getMessage(),
            ]), $this->socketId));
        }
    }
}
