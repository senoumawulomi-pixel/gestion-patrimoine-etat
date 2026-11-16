@extends('layout')
@section('content')
<section class="bg-[#1E3A5F] text-white rounded-2xl p-6 md:p-10 mb-6">
  <p class="text-sm uppercase tracking-widest text-[#C9A227] mb-2">SIGPE</p>
  <h1 class="text-3xl md:text-4xl font-bold mb-3">Patrimoine public</h1>
  <p class="max-w-2xl text-slate-200">Consultez les fiches publiques des patrimoines naturels et vivants de l'État et des collectivités.</p>
</section>

<div class="flex flex-wrap items-center gap-2 mb-4">
  <h2 class="text-2xl font-bold text-[#1E3A5F] mr-auto">Fiches publiées</h2>
</div>
<form method="GET" class="flex flex-wrap gap-2 mb-4">
  <input name="q" value="{{ request('q') }}" placeholder="Rechercher (code, désignation, zone)" class="border rounded px-3 py-2 flex-1 min-w-[200px]">
  <select name="categorie_id" class="border rounded px-3 py-2"><option value="">Toutes catégories</option>
    @foreach($categories as $c)<option value="{{ $c['id'] }}" @selected(request('categorie_id') == $c['id'])>{{ $c['nom'] }}</option>@endforeach</select>
  <select name="type_patrimoine" class="border rounded px-3 py-2"><option value="">Tous types</option>
    @foreach(['naturel' => 'Naturel', 'vivant' => 'Vivant'] as $value => $label)<option value="{{ $value }}" @selected(request('type_patrimoine') === $value)>{{ $label }}</option>@endforeach</select>
  <button class="px-4 py-2 bg-slate-700 text-white rounded">Filtrer</button>
</form>
@if(!$biens)<p class="p-4 bg-yellow-100 rounded">Service Patrimoine indisponible.</p>@else
<div class="bg-white rounded-xl shadow overflow-x-auto">
<table class="w-full text-sm">
  <thead class="bg-slate-50 text-left"><tr><th class="p-3">Fiche</th><th>Type</th><th>Catégorie</th><th>Zone générale</th><th>Description publique</th></tr></thead>
  <tbody>
  @forelse($biens['data'] as $b)
    <tr class="border-t hover:bg-slate-50">
      <td class="p-3"><a class="text-[#1E3A5F] font-medium underline" href="{{ route('public.biens.show', $b['id']) }}">{{ $b['designation'] }}</a><div class="text-xs text-slate-500">{{ $b['code'] }}</div></td>
      <td>{{ $b['type_patrimoine'] }}</td><td>{{ $b['categorie']['nom'] }}</td>
      <td>{{ $b['zone_publique'] ?? '–' }}</td><td>{{ $b['description_publique'] ?? '–' }}</td>
    </tr>
  @empty <tr><td colspan="5" class="p-4 text-center text-slate-500">Aucun patrimoine naturel ou vivant publié pour le moment.</td></tr> @endforelse
  </tbody>
</table></div>
<div class="flex justify-between mt-3 text-sm">
  <span>Page {{ $biens['current_page'] }} / {{ $biens['last_page'] }} – {{ $biens['total'] }} fiche(s)</span>
  <span class="space-x-2">
    @if($biens['current_page'] > 1)<a class="underline" href="{{ route('public.home', array_merge(request()->query(), ['page' => $biens['current_page'] - 1])) }}">Précédent</a>@endif
    @if($biens['current_page'] < $biens['last_page'])<a class="underline" href="{{ route('public.home', array_merge(request()->query(), ['page' => $biens['current_page'] + 1])) }}">Suivant</a>@endif
  </span>
</div>
@endif
@endsection
