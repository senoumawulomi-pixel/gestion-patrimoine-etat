@extends('layout')
@section('content')
<div class="max-w-sm mx-auto mt-16 bg-white p-6 rounded-xl shadow">
  <h1 class="text-xl font-bold text-[#1E3A5F] mb-1">SIGPE</h1>
  <p class="text-sm text-slate-500 mb-4">Gestion du patrimoine de l'État</p>
  <form method="POST" action="{{ url('/login') }}" class="space-y-3">@csrf
    <input type="email" name="email" value="{{ old('email') }}" placeholder="E-mail" required class="w-full border rounded px-3 py-2">
    <input type="password" name="password" placeholder="Mot de passe" required class="w-full border rounded px-3 py-2">
    <button class="w-full bg-[#1E3A5F] text-white py-2 rounded hover:opacity-90">Se connecter</button>
  </form>
</div>
@endsection
