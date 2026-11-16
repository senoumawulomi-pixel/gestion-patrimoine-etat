@extends('layout')
@section('content')
@php $organisationNames = collect($organisations)->keyBy('id'); $central = session('user.role') === 'admin_central'; @endphp
<div class="flex flex-wrap items-center gap-2 mb-4">
  <h1 class="text-2xl font-bold text-[#1E3A5F] mr-auto">Actifs financiers et dettes</h1>
  @if(in_array(session('user.role'), ['admin_central','admin','gestionnaire'], true))
  <a href="{{ route('finances.create') }}" class="px-3 py-2 bg-[#1E3A5F] text-white rounded text-sm">Nouvel enregistrement</a>
  @endif
</div>
<form method="GET" class="flex flex-wrap gap-2 mb-4">
  <input name="q" value="{{ request('q') }}" placeholder="Rechercher" class="border rounded px-3 py-2 flex-1 min-w-[200px]">
  <select name="nature" class="border rounded px-3 py-2"><option value="">Toutes natures</option>
    <option value="actif_financier" @selected(request('nature') === 'actif_financier')>Actif financier</option>
    <option value="dette" @selected(request('nature') === 'dette')>Dette</option></select>
  <select name="statut" class="border rounded px-3 py-2"><option value="">Tous statuts</option>
    <option value="actif" @selected(request('statut') === 'actif')>Actif</option>
    <option value="clos" @selected(request('statut') === 'clos')>Clos</option></select>
  <button class="px-4 py-2 bg-slate-700 text-white rounded">Filtrer</button>
</form>
@if(!$engagements)<p class="p-4 bg-yellow-100 rounded">Service Patrimoine indisponible.</p>@else
<div class="bg-white rounded-xl shadow overflow-x-auto">
<table class="w-full text-sm">
  <thead class="bg-slate-50 text-left"><tr><th class="p-3">Code</th>@if($central)<th>Espace</th>@endif<th>Désignation</th><th>Nature</th><th>Contrepartie</th><th>Montant initial</th><th>Solde</th><th>Échéance</th><th>Statut</th><th></th></tr></thead>
  <tbody>
  @forelse($engagements['data'] as $engagement)
    <tr class="border-t">
      <td class="p-3">{{ $engagement['code'] }}</td>
      @if($central)<td>{{ $organisationNames[$engagement['organisation_id']]['nom'] ?? $engagement['organisation_id'] }}</td>@endif
      <td>{{ $engagement['designation'] }}</td>
      <td>{{ $engagement['nature'] === 'dette' ? 'Dette' : 'Actif financier' }}</td>
      <td>{{ $engagement['contrepartie'] }}</td>
      <td>{{ number_format($engagement['montant_initial'], 2, ',', ' ') }}</td>
      <td>{{ number_format($engagement['solde_courant'], 2, ',', ' ') }}</td>
      <td>{{ $engagement['date_echeance'] ?? '–' }}</td>
      <td>{{ $engagement['statut'] }}</td>
      <td>@if(in_array(session('user.role'), ['admin_central','admin','gestionnaire'], true))<a class="underline" href="{{ route('finances.edit', $engagement['id']) }}">Modifier</a>@endif</td>
    </tr>
  @empty <tr><td colspan="{{ $central ? 10 : 9 }}" class="p-4 text-center text-slate-500">Aucun enregistrement financier dans cet espace.</td></tr>@endforelse
  </tbody>
</table></div>
@endif
<p class="mt-3 text-sm text-slate-600">Ce registre de suivi ne remplace pas un système de comptabilité générale.</p>
@endsection
