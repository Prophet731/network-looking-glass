<?php

namespace App\Console\Commands;

use App\Jobs\Asn\IpLookup;
use Illuminate\Console\Command;

class TestPusherStream extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'app:testws';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        //        IpLookup::dispatch()
    }
}
