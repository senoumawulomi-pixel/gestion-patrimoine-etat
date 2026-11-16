<?php

namespace App\Support;

use Illuminate\Support\Facades\Http;

class Api
{
    public static function base(string $svc): ?string
    {
        return [
            'auth' => env('AUTH_URL'),
            'patrimoine' => env('PATRIMOINE_URL'),
            'mouvement' => env('MOUVEMENT_URL'),
        ][$svc] ?? null;
    }

    /** Appelle un micro-service avec le jeton de la session. */
    public static function call(string $svc, string $method, string $path, array $data = [])
    {
        $req = Http::acceptJson()->timeout(10);
        if ($t = session('token')) {
            $req = $req->withToken($t);
        }
        $url = rtrim(self::base($svc), '/').'/api/'.ltrim($path, '/');
        return $req->send(strtoupper($method), $url, strtolower($method) === 'get' ? ['query' => $data] : ['json' => $data]);
    }
}
