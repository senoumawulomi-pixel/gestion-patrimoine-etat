@extends('layout')
@section('content')
@php $organisationNames = collect($organisations)->keyBy('id'); @endphp
<h1 class="text-2xl font-bold text-[#1E3A5F] mb-4">Espaces d'administration</h1>
<div class="bg-white rounded-xl shadow overflow-x-auto mb-6">
<table class="w-full text-sm">
  <thead class="bg-slate-50 text-left"><tr><th class="p-3">Nom</th><th>Code</th><th>Type</th><th>Parent</th></tr></thead>
  <tbody>
  @forelse($organisations as $organisation)
    <tr class="border-t"><td class="p-3">{{ $organisation['nom'] }}</td><td>{{ $organisation['code'] }}</td><td>{{ $organisation['type'] }}</td><td>{{ $organisationNames[$organisation['parent_id'] ?? '']['nom'] ?? '–' }}</td></tr>
  @empty <tr><td colspan="4" class="p-4 text-center text-slate-500">Aucun espace.</td></tr>@endforelse
  </tbody>
</table></div>
@if($canCreate)
<form method="POST" action="{{ route('organisations.store') }}" class="bg-white p-6 rounded-xl shadow grid md:grid-cols-2 gap-4">
  @csrf
  <h2 class="md:col-span-2 font-semibold">Créer un espace</h2>
  <label class="block">Nom *<input name="nom" required maxlength="150" value="{{ old('nom') }}" class="w-full border rounded px-3 py-2"></label>
  <label class="block">Code unique *<input name="code" required maxlength="50" value="{{ old('code') }}" class="w-full border rounded px-3 py-2"></label>
  <label class="block">Type *
    <select id="organisation-type" name="type" required class="w-full border rounded px-3 py-2">
      <option value="ministere" @selected(old('type', 'ministere') === 'ministere')>Ministère</option>
      <option value="region" @selected(old('type') === 'region')>Région</option>
      <option value="commune" @selected(old('type') === 'commune')>Commune</option>
    </select>
  </label>
  <label class="block">Organisation parente
    <select id="organisation-parent" name="parent_id" class="w-full border rounded px-3 py-2">
      <option value="">Aucun parent (région)</option>
      @foreach($organisations as $parent)
        @if(in_array($parent['type'], ['administration_centrale', 'region'], true))
        <option value="{{ $parent['id'] }}" data-type="{{ $parent['type'] }}" @selected(old('parent_id') === $parent['id'] || (!old('parent_id') && $parent['type'] === 'administration_centrale'))>{{ $parent['nom'] }} ({{ $parent['type'] }})</option>
        @endif
      @endforeach
    </select>
  </label>
  <div class="md:col-span-2"><button class="px-4 py-2 bg-[#1E3A5F] text-white rounded">Créer l'espace</button></div>
</form>
<script>
  const organisationType = document.getElementById('organisation-type');
  const organisationParent = document.getElementById('organisation-parent');
  const updateParent = () => {
    if (organisationType.value === 'ministere') {
      organisationParent.value = organisationParent.querySelector('[data-type="administration_centrale"]').value;
    } else {
      organisationParent.value = '';
    }
  };
  organisationType.addEventListener('change', updateParent);
</script>
@endif
@endsection
