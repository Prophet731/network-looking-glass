<?php

namespace App\Http\Controllers\Api;

use App\Events\UserMtrRequestEvent;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\Process\Exception\ProcessFailedException;
use Symfony\Component\Process\ExecutableFinder;
use Symfony\Component\Process\Process;

class MtrController extends Controller
{
    public function show(Request $request, string $hostname)
    {
        // Check if the hostname is an IP address, if so, do nothing, otherwise resolve it.
        if (! filter_var($hostname, FILTER_VALIDATE_IP)) {
            $hostname = gethostbyname($hostname);
        }

        $query = cache()->remember(sprintf('mtr-%s', hash('xxh128', $hostname)), now()->addMinutes(30), function () use ($hostname) {
            $executableFinder = new ExecutableFinder();
            $mtrPath = $executableFinder->find('mtr');

            $process = new Process([
                $mtrPath,
                '-z',
                '-j',
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

            return $output;
        });

        if (request()->hasHeader('X-Socket-Id')) {
            $socketId = request()->header('X-Socket-Id');
            broadcast(new UserMtrRequestEvent($socketId, $query));
        }

        return response()->json($query);
    }
}
