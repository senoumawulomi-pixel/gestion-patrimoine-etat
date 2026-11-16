<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Organisation;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class AuthController extends Controller
{
    private const PUBLIC = ['id', 'name', 'email', 'role', 'organisation_id'];

    /** Retrouve l'utilisateur à partir du jeton Bearer (haché en base). */
    private function resolve(Request $request): ?User
    {
        $token = $request->bearerToken();
        return $token ? User::where('api_token', hash('sha256', $token))->first() : null;
    }

    private function scopeOrganisationIds(User $user, ?Organisation $organisation): array
    {
        if ($user->role === 'admin_central' && $organisation?->id === Organisation::CENTRAL_ID) {
            return Organisation::query()
                ->whereKey(Organisation::CENTRAL_ID)
                ->orWhere(function ($query) {
                    $query->where('parent_id', Organisation::CENTRAL_ID)
                        ->where('type', 'ministere');
                })
                ->pluck('id')
                ->all();
        }

        return $organisation ? [$organisation->id] : [];
    }

    private function userData(User $user): array
    {
        $organisation = Organisation::find($user->organisation_id);
        $data = $user->only(self::PUBLIC);
        $data['organisation'] = $organisation?->only(['id', 'nom', 'code', 'type', 'parent_id']);
        $data['scope_organisation_ids'] = $this->scopeOrganisationIds($user, $organisation);

        return $data;
    }

    private function canAdministerUsers(User $user): bool
    {
        return in_array($user->role, ['admin_central', 'admin'], true);
    }

    public function login(Request $request)
    {
        $request->validate(['email' => 'required|email', 'password' => 'required|string']);
        $user = User::where('email', $request->email)->first();
        if (! $user || ! Hash::check($request->password, $user->password)) {
            return response()->json(['message' => 'Identifiants invalides'], 422);
        }
        $plain = Str::random(60);
        $user->forceFill(['api_token' => hash('sha256', $plain)])->save();
        return ['token' => $plain, 'user' => $this->userData($user)];
    }

    public function logout(Request $request)
    {
        if ($user = $this->resolve($request)) {
            $user->forceFill(['api_token' => null])->save();
        }
        return response()->json(['message' => 'Déconnecté']);
    }

    /** Utilisé par les autres services pour valider un jeton. */
    public function me(Request $request)
    {
        $user = $this->resolve($request);
        return $user ? $this->userData($user) : response()->json(['message' => 'Non authentifié'], 401);
    }

    public function users(Request $request)
    {
        $user = $this->resolve($request);
        if (! $user) return response()->json(['message' => 'Non authentifié'], 401);
        if (! $this->canAdministerUsers($user)) return response()->json(['message' => 'Accès refusé'], 403);

        $query = User::select(self::PUBLIC)->orderBy('name');
        if ($user->role === 'admin_central') {
            $scope = $this->scopeOrganisationIds($user, Organisation::find($user->organisation_id));
            $query->whereIn('organisation_id', $scope);
        } else {
            $query->where('organisation_id', $user->organisation_id);
        }

        return $query->get();
    }

    public function createUser(Request $request)
    {
        $user = $this->resolve($request);
        if (! $user) return response()->json(['message' => 'Non authentifié'], 401);
        if (! $this->canAdministerUsers($user)) return response()->json(['message' => 'Accès refusé'], 403);
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|string|min:8',
            'role' => 'required|in:admin_central,admin,gestionnaire,auditeur',
            'organisation_id' => 'sometimes|nullable|uuid|exists:organisations,id',
        ]);

        $organisationId = $data['organisation_id'] ?? $user->organisation_id;
        if ($user->role === 'admin') {
            $organisationId = $user->organisation_id;
            if ($data['role'] === 'admin_central') {
                return response()->json(['message' => 'Accès refusé'], 403);
            }
        } elseif ($data['role'] === 'admin_central' && $organisationId !== Organisation::CENTRAL_ID) {
            return response()->json(['message' => 'Un administrateur central doit être rattaché à l’administration centrale'], 422);
        }

        $new = new User();
        $new->forceFill([
            'name' => $data['name'], 'email' => $data['email'],
            'password' => Hash::make($data['password']), 'role' => $data['role'],
            'organisation_id' => $organisationId,
        ])->save();
        return response()->json($new->only(self::PUBLIC), 201);
    }

    public function organisations(Request $request)
    {
        $user = $this->resolve($request);
        if (! $user) return response()->json(['message' => 'Non authentifié'], 401);

        if ($user->role === 'admin_central') {
            return Organisation::orderBy('nom')->get(['id', 'nom', 'code', 'type', 'parent_id']);
        }
        if ($user->role === 'admin') {
            return Organisation::whereKey($user->organisation_id)->get(['id', 'nom', 'code', 'type', 'parent_id']);
        }

        return response()->json(['message' => 'Accès refusé'], 403);
    }

    public function createOrganisation(Request $request)
    {
        $user = $this->resolve($request);
        if (! $user) return response()->json(['message' => 'Non authentifié'], 401);
        if ($user->role !== 'admin_central' || $user->organisation_id !== Organisation::CENTRAL_ID) {
            return response()->json(['message' => 'Accès refusé'], 403);
        }

        $data = $request->validate([
            'nom' => 'required|string|max:150',
            'code' => 'required|string|max:50|unique:organisations,code',
            'type' => 'required|in:ministere,region,commune',
            'parent_id' => 'nullable|uuid|exists:organisations,id',
        ]);

        if ($data['type'] === 'ministere' && ($data['parent_id'] ?? null) !== Organisation::CENTRAL_ID) {
            return response()->json(['message' => 'Un ministère doit dépendre de l’administration centrale'], 422);
        }
        if ($data['type'] === 'region' && ! empty($data['parent_id'])) {
            return response()->json(['message' => 'Une région est une organisation autonome'], 422);
        }
        if ($data['type'] === 'commune' && ! empty($data['parent_id'])) {
            $parent = Organisation::find($data['parent_id']);
            if (! $parent || $parent->type !== 'region') {
                return response()->json(['message' => 'Une commune peut uniquement dépendre d’une région'], 422);
            }
        }

        $organisation = new Organisation($data);
        $organisation->id = (string) Str::uuid();
        $organisation->save();

        return response()->json($organisation->only(['id', 'nom', 'code', 'type', 'parent_id']), 201);
    }
}
