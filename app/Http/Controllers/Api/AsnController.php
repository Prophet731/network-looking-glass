<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class AsnController extends Controller
{
    public function __invoke(int $asn)
    {
        try {
            $data = cache()->remember(sprintf('asn-%d', $asn), now()->addMinutes(30), function () use ($asn) {
                $response = Http::get(sprintf('https://api.bgpview.io/asn/%d', $asn));
                $response->throw();
                return collect($response->json()['data']);
            });

            return response()->json($data);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
}
