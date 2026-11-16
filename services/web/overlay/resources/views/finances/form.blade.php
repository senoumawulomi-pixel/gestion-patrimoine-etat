@extends('layout')
@section('content')
@php $e = $engagement; $v = fn($key, $default = '') => old($key, $e[$key] ?? $default); @endphp
<h1 class="text-2xl font-bold text-[#1E3A5F] mb-4">{{ $e ? 'Modifier '.$e['code'] : 'Nouvel actif financier ou dette' }}</h1>
<form method="POST" action="{{ $e ? route('finances.update', $e['id']) : route('finances.store') }}" class="bg-white p-6 rounded-xl shadow grid md:grid-cols-2 gap-4">
  @csrf @if($e) @method('PUT') @endif
  @if(session('user.role') === 'admin_central' && ! $e)
  <label class="block">Espace propriétaire *
    <select name="organisation_id" required class="w-full border rounded px-3 py-2">
      @foreach($organisations as $organisation)
      <option value="{{ $organisation['id'] }}" @selected($v('organisation_id', session('user.organisation_id')) === $organisation['id'])>{{ $organisation['nom'] }}</option>
      @endforeach
    </select>
  </label>
  @endif
  <label class="block">Code *<input name="code" value="{{ $v('code') }}" required maxlength="50" class="w-full border rounded px-3 py-2"></label>
  <label class="block">Nature *
    <select name="nature" required class="w-full border rounded px-3 py-2">
      <option value="actif_financier" @selected($v('nature') === 'actif_financier')>Actif financier</option>
      <option value="dette" @selected($v('nature') === 'dette')>Dette</option>
    </select>
  </label>
  <label class="block">Désignation *<input name="designation" value="{{ $v('designation') }}" required maxlength="255" class="w-full border rounded px-3 py-2"></label>
  <label class="block">Contrepartie *<input name="contrepartie" value="{{ $v('contrepartie') }}" required maxlength="255" class="w-full border rounded px-3 py-2"></label>
  <label class="block">Montant initial *<input type="number" step="0.01" min="0" name="montant_initial" value="{{ $v('montant_initial') }}" required class="w-full border rounded px-3 py-2"></label>
  <label class="block">Solde courant *<input type="number" step="0.01" min="0" name="solde_courant" value="{{ $v('solde_courant') }}" required class="w-full border rounded px-3 py-2"></label>
  <label class="block">Date d'effet *<input type="date" name="date_effet" value="{{ $v('date_effet') }}" required class="w-full border rounded px-3 py-2"></label>
  <label class="block">Date d'échéance<input type="date" name="date_echeance" value="{{ $v('date_echeance') }}" class="w-full border rounded px-3 py-2"></label>
  <label class="block">Statut
    <select name="statut" class="w-full border rounded px-3 py-2">
      <option value="actif" @selected($v('statut', 'actif') === 'actif')>Actif</option>
      <option value="clos" @selected($v('statut') === 'clos')>Clos</option>
    </select>
  </label>
  <label class="block md:col-span-2">Notes internes<textarea name="notes" maxlength="5000" class="w-full border rounded px-3 py-2">{{ $v('notes') }}</textarea></label>
  <div class="md:col-span-2 flex gap-2">
    <button class="px-4 py-2 bg-[#1E3A5F] text-white rounded">Enregistrer</button>
    <a href="{{ route('finances.index') }}" class="px-4 py-2 bg-slate-200 rounded">Annuler</a>
  </div>
</form>
@if($e && in_array(session('user.role'), ['admin_central','admin'], true))
<form method="POST" action="{{ route('finances.destroy', $e['id']) }}" class="mt-3" onsubmit="return confirm('Supprimer cet enregistrement ?')">
  @csrf @method('DELETE')
  <button class="px-4 py-2 bg-red-600 text-white rounded">Supprimer</button>
</form>
@endif
<p class="mt-3 text-sm text-slate-600">Ce registre de suivi ne remplace pas un système de comptabilité générale.</p>
@endsection
