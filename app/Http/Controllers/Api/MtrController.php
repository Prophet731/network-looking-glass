<?php

namespace App\Http\Controllers\Api;

use App\Events\UserMtrRequestEvent;
use App\Http\Controllers\Controller;
use App\Traits\NetworkHelpersTrait;
use Illuminate\Http\Request;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

class MtrController extends Controller
{
    use NetworkHelpersTrait;

    public function show(Request $request, string $hostname)
    {
        // Check if the hostname is an IP address, if so, do nothing, otherwise resolve it.
        if (! $this->validateHostname($hostname)) {
            $hostname = gethostbyname($hostname);
        }

        $query = cache()->remember(sprintf('mtr-%s', hash('xxh128', $hostname)), now()->addMinutes(30), function () use ($hostname) {
            try {
                $executableFinder = new ExecutableFinder();
                $mtrPath = $executableFinder->find('mtr');

                $process = new Process([
                    $mtrPath,
                    '-z',
                    '-j',
                    '-b',
                    $hostname,
                ]);

                $process->run();

                // Executes after the command finishes
                if (! $process->isSuccessful()) {
                    throw new ProcessFailedException($process);
                }

                try {
                    $output = collect(json_decode($process->getOutput(), true, 512, JSON_THROW_ON_ERROR)['report']);
                } catch (\JsonException $e) {
                    throw new \RuntimeException($e->getMessage(), 500, $e);
                }

                foreach ($output['hubs'] as $hub) {
                    $hub['ip'] = gethostbyname($hub['host']);
                }

                return $output;
            } catch (\Throwable $e) {
                return [
                    'error' => $e->getMessage(),
                ];
            }
        });

        if (request()->hasHeader('X-Socket-Id')) {
            $socketId = request()->header('X-Socket-Id');
            broadcast(new UserMtrRequestEvent($socketId, $query));
        }

        return response()->json($query);
    }
}
