<?php

namespace App\Http\Controllers;

use App\Support\Api;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/** Passerelle API : /gateway/{service}/{chemin} -> micro-service correspondant. */
class GatewayController extends Controller
{
    public function __invoke(Request $request, string $service, string $path)
    {
        $base = Api::base($service);
        abort_if(! $base, 404, 'Service inconnu');
        $headers = $request->header('Authorization') ? ['Authorization' => $request->header('Authorization')] : [];
        try {
            $res = Http::withHeaders($headers)->acceptJson()->timeout(10)->send(
                $request->method(),
                rtrim($base, '/').'/api/'.$path,
                ['query' => $request->query(), 'json' => $request->json()->all()]
            );
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Service indisponible'], 503);
        }
        return response($res->body(), $res->status())->header('Content-Type', $res->header('Content-Type') ?: 'application/json');
    }
}
