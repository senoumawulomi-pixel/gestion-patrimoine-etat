<?php

namespace App\Http\Controllers;

use App\Support\Api;
use Illuminate\Http\Request;
use Illuminate\Http\Client\ConnectionException;

class WebController extends Controller
{
    private function fail($res, string $default = 'Une erreur est survenue')
    {
        $errors = $res->json('errors');
        if ($errors) {
            return back()->withErrors($errors)->withInput();
        }
        return back()->with('error', $res->json('message') ?? $default)->withInput();
    }

    public function loginForm()
    {
        return session('token') ? redirect()->route('dashboard') : view('login');
    }

    public function login(Request $request)
    {
        try {
            $res = Api::call('auth', 'post', 'login', $request->only('email', 'password'));
        } catch (\Throwable $e) {
            return back()->with('error', 'Service d\'authentification indisponible');
        }
        if (! $res->ok()) {
            return back()->with('error', $res->json('message') ?? 'Connexion impossible')->withInput($request->only('email'));
        }
        session(['token' => $res->json('token'), 'user' => $res->json('user')]);
        return redirect()->route('dashboard');
    }

    public function logout()
    {
        $logoutFailed = false;
        try {
            $logoutFailed = ! Api::call('auth', 'post', 'logout')->successful();
        } catch (ConnectionException $e) {
            $logoutFailed = true;
        }
        session()->flush();
        $response = redirect()->route('public.home');

        return $logoutFailed
            ? $response->with('error', 'La session locale est fermée, mais le jeton distant n’a pas pu être révoqué.')
            : $response;
    }

    public function publicIndex(Request $request)
    {
        $res = Api::call('patrimoine', 'get', 'public/biens', $request->only('q', 'categorie_id', 'type_patrimoine', 'page'));
        $cats = Api::call('patrimoine', 'get', 'public/categories');
        return view('public-home', [
            'biens' => $res->ok() ? $res->json() : null,
            'categories' => $cats->ok() ? $cats->json() : [],
        ]);
    }

    public function publicShow(int $id)
    {
        $bien = Api::call('patrimoine', 'get', "public/biens/$id");
        abort_if(! $bien->ok(), 404);
        return view('biens.public-show', ['bien' => $bien->json()]);
    }

    public function dashboard()
    {
        $res = Api::call('patrimoine', 'get', 'biens/stats');
        return view('dashboard', ['stats' => $res->ok() ? $res->json() : null]);
    }

    public function index(Request $request)
    {
        $res = Api::call('patrimoine', 'get', 'biens', $request->only('q', 'categorie_id', 'type_patrimoine', 'organisation_id', 'statut', 'page'));
        $cats = Api::call('patrimoine', 'get', 'categories');
        return view('biens.index', [
            'biens' => $res->ok() ? $res->json() : null,
            'categories' => $cats->ok() ? $cats->json() : [],
            'organisations' => $this->organisationOptions(),
        ]);
    }

    public function create()
    {
        return view('biens.form', [
            'bien' => null,
            'categories' => Api::call('patrimoine', 'get', 'categories')->json() ?? [],
            'organisations' => $this->organisationOptions(),
        ]);
    }

    public function store(Request $request)
    {
        $res = Api::call('patrimoine', 'post', 'biens', array_filter($request->except('_token'), fn ($v) => $v !== null && $v !== ''));
        return $res->successful() ? redirect()->route('biens.show', $res->json('id'))->with('ok', 'Bien créé') : $this->fail($res);
    }

    public function show(int $id)
    {
        $bien = Api::call('patrimoine', 'get', "biens/$id");
        abort_if(! $bien->ok(), 404);
        $mv = Api::call('mouvement', 'get', 'mouvements', ['bien_id' => $id]);
        return view('biens.show', ['bien' => $bien->json(), 'mouvements' => $mv->ok() ? $mv->json('data') : null]);
    }

    public function edit(int $id)
    {
        $bien = Api::call('patrimoine', 'get', "biens/$id");
        abort_if(! $bien->ok(), 404);
        return view('biens.form', [
            'bien' => $bien->json(),
            'categories' => Api::call('patrimoine', 'get', 'categories')->json() ?? [],
            'organisations' => $this->organisationOptions(),
        ]);
    }

    public function update(Request $request, int $id)
    {
        $res = Api::call('patrimoine', 'put', "biens/$id", $request->except('_token', '_method'));
        return $res->ok() ? redirect()->route('biens.show', $id)->with('ok', 'Bien mis à jour') : $this->fail($res);
    }

    public function destroy(int $id)
    {
        $res = Api::call('patrimoine', 'delete', "biens/$id");
        return $res->ok() ? redirect()->route('biens.index')->with('ok', 'Bien supprimé') : back()->with('error', $res->json('message') ?? 'Suppression impossible');
    }

    public function mouvement(Request $request, int $id)
    {
        $data = array_filter($request->except('_token'), fn ($v) => $v !== null && $v !== '');
        $res = Api::call('mouvement', 'post', 'mouvements', $data + ['bien_id' => $id]);
        return $res->successful() ? back()->with('ok', 'Mouvement enregistré') : $this->fail($res);
    }

    public function export(Request $request)
    {
        $res = Api::call('patrimoine', 'get', 'biens/export', $request->only('q', 'categorie_id', 'type_patrimoine', 'organisation_id', 'statut'));
        return response($res->body(), $res->status(), [
            'Content-Type' => 'text/csv; charset=UTF-8',
            'Content-Disposition' => 'attachment; filename="inventaire.csv"',
        ]);
    }

    private function allOrganisationOptions(): array
    {
        if (session('user.role') !== 'admin_central') {
            $organisation = session('user.organisation');
            return $organisation ? [$organisation] : [];
        }

        $res = Api::call('auth', 'get', 'organisations');
        abort_if(! $res->ok(), 503, 'Service Auth indisponible');

        return $res->json() ?? [];
    }

    private function organisationOptions(): array
    {
        $scope = session('user.scope_organisation_ids', []);

        return array_values(array_filter(
            $this->allOrganisationOptions(),
            fn ($organisation) => in_array($organisation['id'], $scope, true)
        ));
    }

    public function organisations()
    {
        $res = Api::call('auth', 'get', 'organisations');
        abort_if(! $res->ok(), $res->status() ?: 503);

        return view('organisations.index', [
            'organisations' => $res->json() ?? [],
            'canCreate' => session('user.role') === 'admin_central',
        ]);
    }

    public function storeOrganisation(Request $request)
    {
        $res = Api::call('auth', 'post', 'organisations', $request->except('_token'));

        return $res->successful() ? back()->with('ok', 'Espace créé') : $this->fail($res);
    }

    public function users()
    {
        $res = Api::call('auth', 'get', 'users');
        abort_if(! $res->ok(), $res->status() ?: 503);

        return view('users.index', [
            'users' => $res->json() ?? [],
            'organisations' => session('user.role') === 'admin_central'
                ? $this->allOrganisationOptions()
                : $this->organisationOptions(),
            'canCreateOrganisationUser' => session('user.role') === 'admin_central',
        ]);
    }

    public function storeUser(Request $request)
    {
        $res = Api::call('auth', 'post', 'users', $request->except('_token'));

        return $res->successful() ? back()->with('ok', 'Utilisateur créé') : $this->fail($res);
    }

    public function finances(Request $request)
    {
        $res = Api::call('patrimoine', 'get', 'engagements-financiers', $request->only('q', 'nature', 'statut', 'page'));

        return view('finances.index', [
            'engagements' => $res->ok() ? $res->json() : null,
            'organisations' => $this->organisationOptions(),
        ]);
    }

    public function createFinance()
    {
        return view('finances.form', [
            'engagement' => null,
            'organisations' => $this->organisationOptions(),
        ]);
    }

    public function storeFinance(Request $request)
    {
        $res = Api::call('patrimoine', 'post', 'engagements-financiers', $request->except('_token'));

        return $res->successful()
            ? redirect()->route('finances.index')->with('ok', 'Enregistrement créé')
            : $this->fail($res);
    }

    public function editFinance(int $id)
    {
        $res = Api::call('patrimoine', 'get', "engagements-financiers/$id");
        abort_if(! $res->ok(), 404);

        return view('finances.form', [
            'engagement' => $res->json(),
            'organisations' => $this->organisationOptions(),
        ]);
    }

    public function updateFinance(Request $request, int $id)
    {
        $res = Api::call('patrimoine', 'put', "engagements-financiers/$id", $request->except('_token', '_method'));

        return $res->ok()
            ? redirect()->route('finances.index')->with('ok', 'Enregistrement mis à jour')
            : $this->fail($res);
    }

    public function destroyFinance(int $id)
    {
        $res = Api::call('patrimoine', 'delete', "engagements-financiers/$id");

        return $res->ok()
            ? redirect()->route('finances.index')->with('ok', 'Enregistrement supprimé')
            : $this->fail($res);
    }
}
