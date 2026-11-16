@extends('layout')
@section('content')
@php $organisationNames = collect($organisations)->keyBy('id'); @endphp
<h1 class="text-2xl font-bold text-[#1E3A5F] mb-4">Utilisateurs</h1>
<div class="bg-white rounded-xl shadow overflow-x-auto mb-6">
<table class="w-full text-sm">
  <thead class="bg-slate-50 text-left"><tr><th class="p-3">Nom</th><th>E-mail</th><th>Rôle</th><th>Organisation</th></tr></thead>
  <tbody>
  @forelse($users as $user)
    <tr class="border-t"><td class="p-3">{{ $user['name'] }}</td><td>{{ $user['email'] }}</td><td>{{ $user['role'] }}</td><td>{{ $organisationNames[$user['organisation_id']]['nom'] ?? $user['organisation_id'] }}</td></tr>
  @empty <tr><td colspan="4" class="p-4 text-center text-slate-500">Aucun utilisateur.</td></tr>@endforelse
  </tbody>
</table></div>
<form method="POST" action="{{ route('users.store') }}" class="bg-white p-6 rounded-xl shadow grid md:grid-cols-2 gap-4">
  @csrf
  <h2 class="md:col-span-2 font-semibold">Créer un utilisateur</h2>
  @if($canCreateOrganisationUser)
  <label class="block md:col-span-2">Espace *
    <select name="organisation_id" required class="w-full border rounded px-3 py-2">
      @foreach($organisations as $organisation)
      <option value="{{ $organisation['id'] }}" @selected(old('organisation_id') === $organisation['id'])>{{ $organisation['nom'] }} ({{ $organisation['type'] }})</option>
      @endforeach
    </select>
  </label>
  @else
  <p class="md:col-span-2 text-sm text-slate-600">Nouvel utilisateur rattaché à votre espace : {{ session('user.organisation.nom') }}.</p>
  @endif
  <label class="block">Nom *<input name="name" required maxlength="100" value="{{ old('name') }}" class="w-full border rounded px-3 py-2"></label>
  <label class="block">E-mail *<input type="email" name="email" required value="{{ old('email') }}" class="w-full border rounded px-3 py-2"></label>
  <label class="block">Mot de passe temporaire *<input type="password" name="password" minlength="8" required class="w-full border rounded px-3 py-2"></label>
  <label class="block">Rôle *
    <select name="role" required class="w-full border rounded px-3 py-2">
      <option value="admin">Administrateur d'espace</option>
      <option value="gestionnaire">Gestionnaire</option>
      <option value="auditeur">Auditeur</option>
    </select>
  </label>
  <div class="md:col-span-2"><button class="px-4 py-2 bg-[#1E3A5F] text-white rounded">Créer l'utilisateur</button></div>
</form>
@endsection
