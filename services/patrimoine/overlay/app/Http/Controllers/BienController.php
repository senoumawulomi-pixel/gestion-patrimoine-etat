<?php

namespace App\Http\Controllers;

use App\Models\Bien;
use App\Models\Categorie;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Support\Str;

class BienController extends Controller
{
    private function rules(bool $partial = false, string $type = 'materiel'): array
    {
        $prefix = $partial ? 'sometimes|' : '';
        $materialRule = $type === 'materiel'
            ? ($partial ? 'sometimes|required' : 'required')
            : ($partial ? 'sometimes|nullable' : 'nullable');

        return [
            'code' => $prefix.'nullable|string|max:50',
            'designation' => $prefix.'required|string|max:255',
            'categorie_id' => $prefix.'required|exists:categories,id',
            'localisation' => 'nullable|string|max:255',
            'service_affecte' => 'nullable|string|max:255',
            'etat' => $prefix.'required|in:neuf,bon,usage,hors_service',
            'statut' => 'sometimes|required|in:actif,cede,reforme',
            'date_acquisition' => $materialRule.'|date',
            'valeur_acquisition' => $materialRule.'|numeric|min:0',
            'duree_amortissement' => $materialRule.'|integer|min:1|max:100',
            'type_patrimoine' => 'sometimes|required|in:materiel,naturel,vivant',
            'est_public' => 'sometimes|boolean',
            'zone_publique' => 'nullable|string|max:255',
            'description_publique' => 'nullable|string|max:2000',
        ];
    }

    private function applyFilters(Builder $query, Request $request): Builder
    {
        return $query
            ->when($request->q, fn ($q, $value) => $q->where(function ($where) use ($value) {
                $where->where('designation', 'ilike', "%$value%")
                    ->orWhere('code', 'ilike', "%$value%")
                    ->orWhere('localisation', 'ilike', "%$value%")
                    ->orWhere('zone_publique', 'ilike', "%$value%");
            }))
            ->when($request->categorie_id, fn ($q, $value) => $q->where('categorie_id', $value))
            ->when($request->organisation_id, fn ($q, $value) => $q->where('organisation_id', $value))
            ->when($request->type_patrimoine, fn ($q, $value) => $q->where('type_patrimoine', $value))
            ->when($request->statut, fn ($q, $value) => $q->where('statut', $value))
            ->orderByDesc('id');
    }

    private function filtered(Request $request): Builder
    {
        $user = $request->attributes->get('user');

        return $this->applyFilters(
            Bien::with('categorie')->whereIn('organisation_id', $user['scope_organisation_ids'] ?? []),
            $request
        );
    }

    private function publicData(Bien $bien): array
    {
        return [
            'id' => $bien->id,
            'code' => $bien->code,
            'designation' => $bien->designation,
            'type_patrimoine' => $bien->type_patrimoine,
            'categorie' => ['nom' => $bien->categorie->nom],
            'zone_publique' => $bien->zone_publique,
            'description_publique' => $bien->description_publique,
        ];
    }

    private function assertPublicable(Request $request, array $data, ?Bien $bien = null): void
    {
        $user = $request->attributes->get('user');
        $isAdministrator = in_array($user['role'] ?? null, ['admin_central', 'admin'], true);

        if (array_key_exists('est_public', $data)) {
            abort_unless($isAdministrator, 403);
        }
        if ($bien && $bien->est_public && array_key_exists('type_patrimoine', $data)) {
            abort_unless($isAdministrator, 403);
        }

        $type = $data['type_patrimoine'] ?? $bien?->type_patrimoine ?? 'materiel';
        $isPublic = array_key_exists('est_public', $data)
            ? filter_var($data['est_public'], FILTER_VALIDATE_BOOLEAN)
            : ($bien?->est_public ?? false);

        if ($isPublic && ! in_array($type, ['naturel', 'vivant'], true)) {
            abort(422, 'Seuls les patrimoines naturels et vivants peuvent être publiés');
        }
    }

    private function organisationForWrite(Request $request, array $data): string
    {
        $user = $request->attributes->get('user');
        $organisationId = $user['role'] === 'admin_central'
            ? ($data['organisation_id'] ?? $user['organisation_id'])
            : $user['organisation_id'];

        abort_unless(in_array($organisationId, $user['scope_organisation_ids'] ?? [], true), 403);

        return $organisationId;
    }

    public function index(Request $request)
    {
        return $this->filtered($request)->paginate(15)->withQueryString();
    }

    public function show(Bien $bien)
    {
        $user = request()->attributes->get('user');
        abort_unless(in_array($bien->organisation_id, $user['scope_organisation_ids'] ?? [], true), 404);

        return $bien->load('categorie');
    }

    public function publicIndex(Request $request)
    {
        $query = $this->applyFilters(
            Bien::with('categorie')
                ->where('est_public', true)
                ->whereIn('type_patrimoine', ['naturel', 'vivant']),
            $request
        );
        $page = $query->paginate(15)->withQueryString();
        $page->getCollection()->transform(fn (Bien $bien) => $this->publicData($bien));

        return $page;
    }

    public function publicShow(Bien $bien)
    {
        abort_unless(
            $bien->est_public && in_array($bien->type_patrimoine, ['naturel', 'vivant'], true),
            404
        );

        return $this->publicData($bien->load('categorie'));
    }

    public function publicCategories()
    {
        return Categorie::whereHas('biens', fn ($query) => $query
            ->where('est_public', true)
            ->whereIn('type_patrimoine', ['naturel', 'vivant']))
            ->orderBy('nom')
            ->get(['id', 'nom']);
    }

    public function store(Request $request)
    {
        $type = $request->input('type_patrimoine', 'materiel');
        $data = $request->validate($this->rules(false, $type) + [
            'organisation_id' => 'sometimes|uuid',
        ]);
        $this->assertPublicable($request, $data);
        $data['organisation_id'] = $this->organisationForWrite($request, $data);
        $data['type_patrimoine'] = $type;
        $data['code'] = $data['code'] ?? 'PAT-'.date('Y').'-'.strtoupper(Str::random(5));
        $data['statut'] = $data['statut'] ?? 'actif';
        $request->validate(['code' => [
            Rule::unique('biens', 'code')->where('organisation_id', $data['organisation_id']),
        ]]);

        return response()->json(Bien::create($data)->load('categorie'), 201);
    }

    public function update(Request $request, Bien $bien)
    {
        $user = $request->attributes->get('user');
        abort_unless(in_array($bien->organisation_id, $user['scope_organisation_ids'] ?? [], true), 404);

        $type = $request->input('type_patrimoine', $bien->type_patrimoine);
        $rules = $this->rules(true, $type);
        $rules['code'] = [
            'sometimes', 'nullable', 'string', 'max:50',
            Rule::unique('biens', 'code')
                ->where('organisation_id', $bien->organisation_id)
                ->ignore($bien->id),
        ];
        $data = $request->validate($rules);

        if ($type === 'materiel' && $bien->type_patrimoine !== 'materiel') {
            $data = array_merge($data, $request->validate([
                'date_acquisition' => 'required|date',
                'valeur_acquisition' => 'required|numeric|min:0',
                'duree_amortissement' => 'required|integer|min:1|max:100',
            ]));
        }

        $this->assertPublicable($request, $data, $bien);
        if ($type !== 'materiel') {
            $data['date_acquisition'] = null;
            $data['valeur_acquisition'] = null;
            $data['duree_amortissement'] = null;
        }
        $bien->update($data);

        return $bien->fresh('categorie');
    }

    public function destroy(Bien $bien)
    {
        $user = request()->attributes->get('user');
        abort_unless(in_array($bien->organisation_id, $user['scope_organisation_ids'] ?? [], true), 404);
        $bien->delete();

        return response()->json(['message' => 'Bien supprimé']);
    }

    public function categories()
    {
        return Categorie::orderBy('nom')->get();
    }

    public function storeCategorie(Request $request)
    {
        $data = $request->validate(['nom' => 'required|string|max:100|unique:categories,nom']);

        return response()->json(Categorie::create($data), 201);
    }

    public function stats()
    {
        $user = request()->attributes->get('user');
        $biens = Bien::with('categorie')
            ->whereIn('organisation_id', $user['scope_organisation_ids'] ?? [])
            ->get();
        $actifs = $biens->where('statut', 'actif');

        return [
            'total_biens' => $biens->count(),
            'valeur_acquisition' => round($actifs->sum('valeur_acquisition'), 2),
            'valeur_nette' => round($actifs->sum('valeur_nette'), 2),
            'par_categorie' => $actifs->groupBy(fn ($bien) => $bien->categorie->nom)
                ->map(fn ($group) => [
                    'nombre' => $group->count(),
                    'valeur_nette' => round($group->sum('valeur_nette'), 2),
                ]),
            'par_statut' => $biens->groupBy('statut')->map->count(),
            'a_reformer' => $actifs->where('etat', 'hors_service')->values()
                ->map(fn ($bien) => $bien->only(['id', 'code', 'designation', 'service_affecte'])),
        ];
    }

    public function export(Request $request)
    {
        $rows = $this->filtered($request)->get();
        $out = fopen('php://temp', 'r+');
        fputcsv($out, [
            'Espace', 'Code', 'Désignation', 'Type patrimoine', 'Catégorie', 'Localisation',
            'Service', 'État', 'Statut', 'Date acquisition', 'Valeur acquisition', 'Valeur nette',
        ]);
        foreach ($rows as $bien) {
            fputcsv($out, [
                $bien->organisation_id, $bien->code, $bien->designation, $bien->type_patrimoine,
                $bien->categorie->nom, $bien->localisation, $bien->service_affecte,
                $bien->etat, $bien->statut, $bien->date_acquisition?->format('Y-m-d'),
                $bien->valeur_acquisition, $bien->valeur_nette,
            ]);
        }
        rewind($out);

        return response(stream_get_contents($out), 200, [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="inventaire.csv"',
        ]);
    }
}
