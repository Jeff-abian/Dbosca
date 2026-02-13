<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\Http;
use Exception;

class RegistryProxyController extends Controller
{
    public function fetchRecords(Request $request, $type)
    {
        // 1. Siguraduhin na valid ang type
        if (!in_array($type, ['birth', 'marriage', 'death'])) {
            return response()->json(['error' => 'Invalid record type'], 400);
        }

        $targetUrl = "http://lcrdev.pylontradingintl.com/api/client/record/list/{$type}";

        try {
            // 2. Dito natin ilalagay ang "Postman settings" para hindi maputol ang connection
            $response = Http::withOptions([
                'verify' => false, // I-bypass ang SSL (parang sa Postman)
                'curl'   => [
                    // ITO ANG FIX SA ERROR 18: Pinupuwersa ang HTTP 1.0 para hindi maputol ang data transfer
                    CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_0,
                    CURLOPT_FORBID_REUSE => true,
                    CURLOPT_FRESH_CONNECT => true,
                ],
            ])
            ->timeout(60) // Bigyan ng sapat na oras ang server na sumagot
            ->get($targetUrl, [
                'search'   => $request->query('search'),
                'per_page' => $request->query('per_page', 10),
                'page'     => $request->query('page', 1),
            ]);

            // 3. Ibalik ang data mula sa target server patungo sa iyong frontend
            return $response->json();

        } catch (Exception $e) {
            // 4. Debugging mode: Ipakita ang totoong error kung sakaling mag-fail pa rin
            return response()->json([
                'error'   => 'Connection failed',
                'message' => $e->getMessage(),
                'hint'    => 'Check if the target server is still up or if your internet is stable.'
            ], 500);
        }
    }
}