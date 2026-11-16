@extends('layout')
@section('content')
<div class="flex flex-wrap items-center gap-2 mb-4">
  <h1 class="text-2xl font-bold text-[#1E3A5F] mr-auto">Inventaire</h1>
  <a href="{{ route('biens.export', request()->query()) }}" class="px-3 py-2 bg-white rounded shadow text-sm">Exporter CSV</a>
  @if(in_array(session('user.role'), ['admin_central','admin','gestionnaire'], true))
  <a href="{{ route('biens.create') }}" class="px-3 py-2 bg-[#1E3A5F] text-white rounded text-sm">Nouveau bien</a>
  @endif
</div>
<form method="GET" class="flex flex-wrap gap-2 mb-4">
  <input name="q" value="{{ request('q') }}" placeholder="Rechercher (code, désignation, lieu)" class="border rounded px-3 py-2 flex-1 min-w-[200px]">
  <select name="categorie_id" class="border rounded px-3 py-2"><option value="">Toutes catégories</option>
    @foreach($categories as $c)<option value="{{ $c['id'] }}" @selected(request('categorie_id') == $c['id'])>{{ $c['nom'] }}</option>@endforeach</select>
  <select name="type_patrimoine" class="border rounded px-3 py-2"><option value="">Tous types</option>
    @foreach(['materiel' => 'Matériel', 'naturel' => 'Naturel', 'vivant' => 'Vivant'] as $value => $label)<option value="{{ $value }}" @selected(request('type_patrimoine') === $value)>{{ $label }}</option>@endforeach</select>
  @if(session('user.role') === 'admin_central')
  <select name="organisation_id" class="border rounded px-3 py-2"><option value="">Tous les espaces de l'État</option>
    @foreach($organisations as $organisation)<option value="{{ $organisation['id'] }}" @selected(request('organisation_id') === $organisation['id'])>{{ $organisation['nom'] }}</option>@endforeach</select>
  @endif
  <select name="statut" class="border rounded px-3 py-2"><option value="">Tous statuts</option>
    @foreach(['actif','cede','reforme'] as $s)<option value="{{ $s }}" @selected(request('statut') == $s)>{{ $s }}</option>@endforeach</select>
  <button class="px-4 py-2 bg-slate-700 text-white rounded">Filtrer</button>
</form>
@if(!$biens)<p class="p-4 bg-yellow-100 rounded">Service Patrimoine indisponible.</p>@else
@php $organisationNames = collect($organisations)->keyBy('id'); $central = session('user.role') === 'admin_central'; @endphp
<div class="bg-white rounded-xl shadow overflow-x-auto">
<table class="w-full text-sm">
  <thead class="bg-slate-50 text-left"><tr><th class="p-3">Code</th><th>Désignation</th><th>Type</th><th>Catégorie</th>@if($central)<th>Espace</th>@endif<th>Service</th><th>État</th><th>Statut</th><th class="text-right pr-3">Valeur nette</th></tr></thead>
  <tbody>
  @forelse($biens['data'] as $b)
    <tr class="border-t hover:bg-slate-50">
      <td class="p-3"><a class="text-[#1E3A5F] font-medium" href="{{ route('biens.show', $b['id']) }}">{{ $b['code'] }}</a></td>
      <td>{{ $b['designation'] }}</td><td>{{ $b['type_patrimoine'] }}</td><td>{{ $b['categorie']['nom'] }}</td>
      @if($central)<td>{{ $organisationNames[$b['organisation_id']]['nom'] ?? $b['organisation_id'] }}</td>@endif
      <td>{{ $b['service_affecte'] ?? '–' }}</td><td>{{ $b['etat'] }}</td><td>{{ $b['statut'] }}</td>
      <td class="text-right pr-3">{{ $b['valeur_nette'] === null ? '–' : number_format($b['valeur_nette'], 0, ',', ' ') }}</td>
    </tr>
  @empty <tr><td colspan="{{ $central ? 9 : 8 }}" class="p-4 text-center text-slate-500">Aucun bien dans votre espace.</td></tr>@endforelse
  </tbody>
</table></div>
<div class="flex justify-between mt-3 text-sm">
  <span>Page {{ $biens['current_page'] }} / {{ $biens['last_page'] }} – {{ $biens['total'] }} bien(s)</span>
  <span class="space-x-2">
    @if($biens['current_page'] > 1)<a class="underline" href="{{ route('biens.index', array_merge(request()->query(), ['page' => $biens['current_page'] - 1])) }}">Précédent</a>@endif
    @if($biens['current_page'] < $biens['last_page'])<a class="underline" href="{{ route('biens.index', array_merge(request()->query(), ['page' => $biens['current_page'] + 1])) }}">Suivant</a>@endif
  </span>
</div>
@endif
@endsection
