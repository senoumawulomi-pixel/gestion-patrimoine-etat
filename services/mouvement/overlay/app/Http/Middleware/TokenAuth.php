<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

/** Valide le jeton Bearer auprès du service Auth et contrôle le rôle (ex: token:admin,gestionnaire). */
class TokenAuth
{
    public function handle(Request $request, Closure $next, string ...$roles)
    {
        $token = $request->bearerToken();
        if (! $token) {
            return response()->json(['message' => 'Non authentifié'], 401);
        }
        try {
            $res = Http::withToken($token)->acceptJson()->timeout(5)
                ->get(rtrim(env('AUTH_URL'), '/').'/api/me');
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Service Auth indisponible'], 503);
        }
        if (! $res->ok()) {
            return response()->json(['message' => 'Jeton invalide'], 401);
        }
        $user = $res->json();
        if ($roles && ! in_array($user['role'], $roles, true)) {
            return response()->json(['message' => 'Accès refusé pour votre rôle'], 403);
        }
        $request->attributes->set('user', $user);
        return $next($request);
    }
}
