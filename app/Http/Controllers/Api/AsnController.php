<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Jobs\Asn\IpLookup;

class AsnController extends Controller
{
    public function __invoke(string $asn)
    {
        $socketId = request()->header('X-Socket-Id');

        IpLookup::dispatch($asn, $socketId);

        return response()->json([
            'status' => 'ok',
            'message' => 'ASN request in progress.',
            'socket_id' => $socketId,
        ]);
    }
}
