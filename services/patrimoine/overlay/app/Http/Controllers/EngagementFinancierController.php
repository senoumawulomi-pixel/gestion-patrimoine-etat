<?php

namespace App\Http\Controllers;

use App\Models\EngagementFinancier;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EngagementFinancierController extends Controller
{
    private function scope(Request $request): Builder
    {
        $user = $request->attributes->get('user');

        return EngagementFinancier::query()
            ->whereIn('organisation_id', $user['scope_organisation_ids'] ?? []);
    }

    private function rules(bool $partial = false): array
    {
        $prefix = $partial ? 'sometimes|' : '';

        return [
            'code' => $prefix.'required|string|max:50',
            'nature' => $prefix.'required|in:actif_financier,dette',
            'designation' => $prefix.'required|string|max:255',
            'contrepartie' => $prefix.'required|string|max:255',
            'montant_initial' => $prefix.'required|numeric|min:0',
            'solde_courant' => $prefix.'required|numeric|min:0',
            'date_effet' => $prefix.'required|date',
            'date_echeance' => 'sometimes|nullable|date',
            'statut' => 'sometimes|required|in:actif,clos',
            'notes' => 'sometimes|nullable|string|max:5000',
        ];
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

    private function validateDates(array $data, ?EngagementFinancier $existing = null): void
    {
        $dateEffet = $data['date_effet'] ?? $existing?->date_effet;
        $dateEcheance = array_key_exists('date_echeance', $data)
            ? $data['date_echeance']
            : $existing?->date_echeance;

        if ($dateEffet && $dateEcheance && Carbon::parse($dateEcheance)->lt(Carbon::parse($dateEffet))) {
            throw ValidationException::withMessages([
                'date_echeance' => 'La date d’échéance doit être postérieure ou égale à la date d’effet.',
            ]);
        }
    }

    public function index(Request $request)
    {
        return $this->scope($request)
            ->when($request->q, fn ($query, $value) => $query->where(function ($where) use ($value) {
                $where->where('code', 'ilike', "%$value%")
                    ->orWhere('designation', 'ilike', "%$value%")
                    ->orWhere('contrepartie', 'ilike', "%$value%");
            }))
            ->when($request->nature, fn ($query, $value) => $query->where('nature', $value))
            ->when($request->statut, fn ($query, $value) => $query->where('statut', $value))
            ->orderByDesc('id')
            ->paginate(15)
            ->withQueryString();
    }

    public function show(Request $request, EngagementFinancier $engagement)
    {
        abort_unless($this->scope($request)->whereKey($engagement->id)->exists(), 404);

        return $engagement;
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules() + ['organisation_id' => 'sometimes|uuid']);
        $data['organisation_id'] = $this->organisationForWrite($request, $data);
        $request->validate([
            'code' => [Rule::unique('engagements_financiers', 'code')
                ->where('organisation_id', $data['organisation_id'])],
        ]);
        $this->validateDates($data);
        $data['statut'] = $data['statut'] ?? 'actif';

        return response()->json(EngagementFinancier::create($data), 201);
    }

    public function update(Request $request, EngagementFinancier $engagement)
    {
        abort_unless($this->scope($request)->whereKey($engagement->id)->exists(), 404);

        $rules = $this->rules(true);
        $rules['code'] = [
            'sometimes', 'required', 'string', 'max:50',
            Rule::unique('engagements_financiers', 'code')
                ->where('organisation_id', $engagement->organisation_id)
                ->ignore($engagement->id),
        ];
        $data = $request->validate($rules);
        $this->validateDates($data, $engagement);
        $engagement->update($data);

        return $engagement->fresh();
    }

    public function destroy(Request $request, EngagementFinancier $engagement)
    {
        abort_unless($this->scope($request)->whereKey($engagement->id)->exists(), 404);
        $engagement->delete();

        return response()->json(['message' => 'Engagement financier supprimé']);
    }
}
