<?php

namespace App\Http\Controllers;

use App\Models\Mouvement;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;

class MouvementController extends Controller
{
    public function index(Request $request)
    {
        $user = $request->attributes->get('user');

        return Mouvement::query()
            ->whereIn('organisation_id', $user['scope_organisation_ids'] ?? [])
            ->when($request->bien_id, fn ($q, $v) => $q->where('bien_id', $v))
            ->when($request->type, fn ($q, $v) => $q->where('type', $v))
            ->orderByDesc('date_mouvement')->orderByDesc('id')
            ->paginate(20)->withQueryString();
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'bien_id' => 'required|integer',
            'type' => 'required|in:transfert,maintenance,cession,reforme',
            'vers_service' => 'required_if:type,transfert|nullable|string|max:255',
            'description' => 'nullable|string',
            'cout' => 'nullable|numeric|min:0',
            'date_mouvement' => 'nullable|date',
        ]);

        $token = $request->bearerToken();
        $api = rtrim(env('PATRIMOINE_URL'), '/').'/api/biens/'.$data['bien_id'];

        try {
            $res = Http::withToken($token)->acceptJson()->timeout(8)->get($api);
        } catch (\Illuminate\Http\Client\ConnectionException $e) {
            return response()->json(['message' => 'Service Patrimoine indisponible'], 503);
        }
        if ($res->status() === 404) {
            return response()->json(['message' => 'Bien introuvable'], 404);
        }
        if (! $res->ok()) {
            return response()->json(['message' => 'Erreur du service Patrimoine'], 502);
        }
        $bien = $res->json();
        if ($bien['statut'] !== 'actif') {
            return response()->json(['message' => 'Ce bien n\'est plus actif'], 422);
        }

        // Mise à jour du bien via l'API du service propriétaire des données
        $changes = match ($data['type']) {
            'transfert' => ['service_affecte' => $data['vers_service']],
            'cession' => ['statut' => 'cede'],
            'reforme' => ['statut' => 'reforme'],
            default => [],
        };
        if ($changes) {
            try {
                $upd = Http::withToken($token)->acceptJson()->timeout(8)->put($api, $changes);
            } catch (\Illuminate\Http\Client\ConnectionException $e) {
                return response()->json(['message' => 'Service Patrimoine indisponible'], 503);
            }
            if (! $upd->ok()) {
                return response()->json(['message' => 'Mise à jour du bien impossible'], 502);
            }
        }

        $mouvement = Mouvement::create([
            'organisation_id' => $bien['organisation_id'],
            'bien_id' => $bien['id'],
            'type' => $data['type'],
            'depuis_service' => $bien['service_affecte'],
            'vers_service' => $data['vers_service'] ?? null,
            'description' => $data['description'] ?? null,
            'cout' => $data['cout'] ?? null,
            'date_mouvement' => $data['date_mouvement'] ?? now()->toDateString(),
            'user_name' => $request->attributes->get('user')['name'],
        ]);

        return response()->json($mouvement, 201);
    }
}
