@extends('layout')
@section('content')
<div class="flex flex-wrap items-center gap-2 mb-4">
  <h1 class="text-2xl font-bold text-[#1E3A5F] mr-auto">{{ $bien['designation'] }} <span class="text-sm text-slate-500">{{ $bien['code'] }}</span></h1>
  <a href="{{ route('public.home') }}" class="px-3 py-2 bg-white rounded shadow text-sm">Retour à l'inventaire public</a>
</div>
<div class="bg-white p-4 rounded-xl shadow">
  <dl class="grid md:grid-cols-2 gap-y-3 text-sm">
    <dt class="text-slate-500">Type de patrimoine</dt><dd>{{ $bien['type_patrimoine'] }}</dd>
    <dt class="text-slate-500">Catégorie</dt><dd>{{ $bien['categorie']['nom'] }}</dd>
    <dt class="text-slate-500">Zone générale</dt><dd>{{ $bien['zone_publique'] ?? '–' }}</dd>
    <dt class="text-slate-500">Description</dt><dd>{{ $bien['description_publique'] ?? '–' }}</dd>
  </dl>
</div>
@endsection
