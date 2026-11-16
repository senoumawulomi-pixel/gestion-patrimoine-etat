@extends('layout')
@section('content')
@php
  $b = $bien;
  $v = fn($key, $default = '') => old($key, $b[$key] ?? $default);
  $canPublish = in_array(session('user.role'), ['admin_central', 'admin'], true);
@endphp
<h1 class="text-2xl font-bold text-[#1E3A5F] mb-4">{{ $b ? 'Modifier '.$b['code'] : 'Nouveau bien' }}</h1>
<form method="POST" action="{{ $b ? route('biens.update', $b['id']) : route('biens.store') }}" class="bg-white p-6 rounded-xl shadow grid md:grid-cols-2 gap-4">
  @csrf @if($b) @method('PUT') @endif
  @if(session('user.role') === 'admin_central' && ! $b)
  <label class="block">Espace propriétaire *
    <select name="organisation_id" required class="w-full border rounded px-3 py-2">
      @foreach($organisations as $organisation)
      <option value="{{ $organisation['id'] }}" @selected($v('organisation_id', session('user.organisation_id')) === $organisation['id'])>{{ $organisation['nom'] }}</option>
      @endforeach
    </select>
  </label>
  @endif
  <label class="block">Code (auto si vide)<input name="code" value="{{ $v('code') }}" class="w-full border rounded px-3 py-2"></label>
  <label class="block">Désignation *<input name="designation" value="{{ $v('designation') }}" required maxlength="255" class="w-full border rounded px-3 py-2"></label>
  <label class="block">Type de patrimoine *
    <select name="type_patrimoine" required class="w-full border rounded px-3 py-2">
      @foreach(['materiel' => 'Matériel', 'naturel' => 'Naturel', 'vivant' => 'Vivant'] as $type => $label)
      <option value="{{ $type }}" @selected($v('type_patrimoine', 'materiel') === $type)>{{ $label }}</option>
      @endforeach
    </select>
  </label>
  <label class="block">Catégorie *<select name="categorie_id" required class="w-full border rounded px-3 py-2">
    @foreach($categories as $c)<option value="{{ $c['id'] }}" @selected($v('categorie_id') == $c['id'])>{{ $c['nom'] }}</option>@endforeach</select></label>
  <label class="block">État *<select name="etat" required class="w-full border rounded px-3 py-2">
    @foreach(['neuf','bon','usage','hors_service'] as $e)<option value="{{ $e }}" @selected($v('etat','bon') == $e)>{{ $e }}</option>@endforeach</select></label>
  <label class="block">Localisation interne<input name="localisation" value="{{ $v('localisation') }}" maxlength="255" class="w-full border rounded px-3 py-2"></label>
  <label class="block">Service affecté<input name="service_affecte" value="{{ $v('service_affecte') }}" maxlength="255" class="w-full border rounded px-3 py-2"></label>
  <div id="materiel-fields" class="md:col-span-2 grid md:grid-cols-3 gap-4">
    <label class="block">Date d'acquisition *<input type="date" name="date_acquisition" value="{{ $b && !empty($b['date_acquisition']) ? substr($b['date_acquisition'], 0, 10) : old('date_acquisition') }}" class="materiel-field w-full border rounded px-3 py-2"></label>
    <label class="block">Valeur d'acquisition *<input type="number" step="0.01" min="0" name="valeur_acquisition" value="{{ $v('valeur_acquisition') }}" class="materiel-field w-full border rounded px-3 py-2"></label>
    <label class="block">Durée d'amortissement (ans) *<input type="number" min="1" max="100" name="duree_amortissement" value="{{ $v('duree_amortissement', 5) }}" class="materiel-field w-full border rounded px-3 py-2"></label>
  </div>
  @if($canPublish)
  <div class="md:col-span-2 border-t pt-4">
    <h2 class="font-semibold mb-2">Publication publique (patrimoine naturel ou vivant)</h2>
    <label class="flex items-center gap-2 mb-3">
      <input type="hidden" name="est_public" value="0">
      <input type="checkbox" name="est_public" value="1" @checked((bool) $v('est_public', false))>
      Publier la fiche dans le catalogue public
    </label>
    <label class="block mb-3">Zone publique (description géographique générale)
      <input name="zone_publique" value="{{ $v('zone_publique') }}" maxlength="255" class="w-full border rounded px-3 py-2">
    </label>
    <label class="block">Description publique
      <textarea name="description_publique" maxlength="2000" class="w-full border rounded px-3 py-2">{{ $v('description_publique') }}</textarea>
    </label>
  </div>
  @endif
  <div class="md:col-span-2 flex gap-2">
    <button class="px-4 py-2 bg-[#1E3A5F] text-white rounded">Enregistrer</button>
    <a href="{{ $b ? route('biens.show', $b['id']) : route('biens.index') }}" class="px-4 py-2 bg-slate-200 rounded">Annuler</a>
  </div>
</form>
<script>
  const typePatrimoine = document.querySelector('[name="type_patrimoine"]');
  const materialFields = document.querySelectorAll('.materiel-field');
  const materialContainer = document.getElementById('materiel-fields');
  const toggleMaterialFields = () => {
    const material = typePatrimoine.value === 'materiel';
    materialContainer.classList.toggle('hidden', !material);
    materialFields.forEach((field) => {
      field.disabled = !material;
      field.required = material;
      if (!material) field.value = '';
    });
  };
  typePatrimoine.addEventListener('change', toggleMaterialFields);
  toggleMaterialFields();
</script>
@endsection
