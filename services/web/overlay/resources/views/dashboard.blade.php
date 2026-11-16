@extends('layout')
@section('content')
<h1 class="text-2xl font-bold text-[#1E3A5F] mb-4">Tableau de bord</h1>
@if(!$stats)
  <p class="p-4 bg-yellow-100 rounded">Service Patrimoine indisponible.</p>
@else
<div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
  <div class="bg-white p-4 rounded-xl shadow"><div class="text-sm text-slate-500">Biens recensés</div><div class="text-2xl font-bold">{{ $stats['total_biens'] }}</div></div>
  <div class="bg-white p-4 rounded-xl shadow"><div class="text-sm text-slate-500">Valeur d'acquisition</div><div class="text-2xl font-bold">{{ number_format($stats['valeur_acquisition'], 0, ',', ' ') }}</div></div>
  <div class="bg-white p-4 rounded-xl shadow"><div class="text-sm text-slate-500">Valeur nette comptable</div><div class="text-2xl font-bold">{{ number_format($stats['valeur_nette'], 0, ',', ' ') }}</div></div>
  <div class="bg-white p-4 rounded-xl shadow"><div class="text-sm text-slate-500">À réformer</div><div class="text-2xl font-bold text-red-700">{{ count($stats['a_reformer']) }}</div></div>
</div>
<div class="grid md:grid-cols-2 gap-4">
  <div class="bg-white p-4 rounded-xl shadow">
    <h2 class="font-semibold mb-2">Par catégorie (valeur nette)</h2>
    @php $max = max(1, collect($stats['par_categorie'])->max('valeur_nette')); @endphp
    @foreach($stats['par_categorie'] as $nom => $c)
      <div class="mb-2"><div class="flex justify-between text-sm"><span>{{ $nom }} ({{ $c['nombre'] }})</span><span>{{ number_format($c['valeur_nette'], 0, ',', ' ') }}</span></div>
      <div class="h-2 bg-slate-200 rounded"><div class="h-2 bg-[#C9A227] rounded" style="width: {{ round($c['valeur_nette'] / $max * 100) }}%"></div></div></div>
    @endforeach
  </div>
  <div class="bg-white p-4 rounded-xl shadow">
    <h2 class="font-semibold mb-2">Biens à réformer</h2>
    @forelse($stats['a_reformer'] as $b)
      <a href="{{ route('biens.show', $b['id']) }}" class="block py-1 border-b hover:text-[#1E3A5F]">{{ $b['code'] }} – {{ $b['designation'] }}</a>
    @empty <p class="text-sm text-slate-500">Aucun bien hors service.</p> @endforelse
  </div>
</div>
@endif
@endsection
