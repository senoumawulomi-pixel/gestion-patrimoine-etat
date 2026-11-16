@extends('layout')
@section('content')
@php $role = session('user.role'); $canWrite = in_array($role, ['admin_central','admin','gestionnaire'], true); @endphp
<div class="flex flex-wrap items-center gap-2 mb-4">
  <h1 class="text-2xl font-bold text-[#1E3A5F] mr-auto">{{ $bien['designation'] }} <span class="text-sm text-slate-500">{{ $bien['code'] }}</span></h1>
  @if($canWrite)<a href="{{ route('biens.edit', $bien['id']) }}" class="px-3 py-2 bg-white rounded shadow text-sm">Modifier</a>@endif
  @if(in_array($role, ['admin_central','admin'], true))
  <form method="POST" action="{{ route('biens.destroy', $bien['id']) }}" onsubmit="return confirm('Supprimer ce bien ?')">@csrf @method('DELETE')
    <button class="px-3 py-2 bg-red-600 text-white rounded text-sm">Supprimer</button></form>
  @endif
</div>
<div class="grid md:grid-cols-2 gap-4">
  <div class="bg-white p-4 rounded-xl shadow">
    <dl class="grid grid-cols-2 gap-y-2 text-sm">
      <dt class="text-slate-500">Type de patrimoine</dt><dd>{{ $bien['type_patrimoine'] }}</dd>
      <dt class="text-slate-500">Catégorie</dt><dd>{{ $bien['categorie']['nom'] }}</dd>
      @if($role === 'admin_central' && isset($bien['organisation_id']))
      <dt class="text-slate-500">Espace propriétaire</dt><dd>{{ $bien['organisation_id'] }}</dd>
      @endif
      <dt class="text-slate-500">Localisation</dt><dd>{{ $bien['localisation'] ?? '–' }}</dd>
      <dt class="text-slate-500">Service affecté</dt><dd>{{ $bien['service_affecte'] ?? '–' }}</dd>
      <dt class="text-slate-500">État</dt><dd>{{ $bien['etat'] }}</dd>
      <dt class="text-slate-500">Statut</dt><dd>{{ $bien['statut'] }}</dd>
      @if($bien['type_patrimoine'] === 'materiel')
      <dt class="text-slate-500">Acquisition</dt><dd>{{ substr($bien['date_acquisition'], 0, 10) }}</dd>
      <dt class="text-slate-500">Valeur d'acquisition</dt><dd>{{ number_format($bien['valeur_acquisition'], 0, ',', ' ') }}</dd>
      <dt class="text-slate-500">Valeur nette</dt><dd class="font-bold">{{ $bien['valeur_nette'] === null ? '–' : number_format($bien['valeur_nette'], 0, ',', ' ') }}</dd>
      @endif
    </dl>
  </div>
  @if($canWrite && $bien['statut'] === 'actif')
  <form method="POST" action="{{ route('biens.mouvement', $bien['id']) }}" class="bg-white p-4 rounded-xl shadow space-y-2">@csrf
    <h2 class="font-semibold">Enregistrer un mouvement</h2>
    <select name="type" class="w-full border rounded px-3 py-2">
      <option value="transfert">Transfert</option><option value="maintenance">Maintenance</option>
      <option value="cession">Cession</option><option value="reforme">Réforme</option></select>
    <input name="vers_service" placeholder="Service de destination (transfert)" class="w-full border rounded px-3 py-2">
    <input type="number" step="0.01" min="0" name="cout" placeholder="Coût (maintenance)" class="w-full border rounded px-3 py-2">
    <textarea name="description" placeholder="Description" class="w-full border rounded px-3 py-2"></textarea>
    <button class="px-4 py-2 bg-[#1E3A5F] text-white rounded">Valider</button>
  </form>
  @endif
</div>
<h2 class="font-semibold mt-6 mb-2">Historique</h2>
@if($mouvements === null)<p class="p-3 bg-yellow-100 rounded">Service Mouvements indisponible.</p>
@else
<div class="bg-white rounded-xl shadow overflow-x-auto"><table class="w-full text-sm">
  <thead class="bg-slate-50 text-left"><tr><th class="p-3">Date</th><th>Type</th><th>De</th><th>Vers</th><th>Coût</th><th>Par</th><th>Description</th></tr></thead>
  <tbody>@forelse($mouvements as $m)
    <tr class="border-t"><td class="p-3">{{ $m['date_mouvement'] }}</td><td>{{ $m['type'] }}</td><td>{{ $m['depuis_service'] }}</td><td>{{ $m['vers_service'] }}</td><td>{{ $m['cout'] }}</td><td>{{ $m['user_name'] }}</td><td>{{ $m['description'] }}</td></tr>
  @empty <tr><td colspan="7" class="p-4 text-center text-slate-500">Aucun mouvement.</td></tr>@endforelse</tbody>
</table></div>
@endif
@endsection
