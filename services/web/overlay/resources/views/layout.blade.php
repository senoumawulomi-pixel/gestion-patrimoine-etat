<!DOCTYPE html>
<html lang="fr">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>SIGPE – Patrimoine de l'État</title>
  <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="bg-slate-100 text-slate-800">
<nav class="bg-[#1E3A5F] text-white">
  <div class="max-w-6xl mx-auto px-4 py-3 flex flex-wrap items-center gap-4">
    <a href="{{ route('public.home') }}" class="font-bold tracking-wide hover:text-[#C9A227]">SIGPE</a>
    <a href="{{ route('public.home') }}" class="hover:text-[#C9A227]">Patrimoine public</a>
    @if(session('token'))
    <a href="{{ route('dashboard') }}" class="hover:text-[#C9A227]">Tableau de bord</a>
    <a href="{{ route('biens.index') }}" class="hover:text-[#C9A227]">Inventaire</a>
    <a href="{{ route('finances.index') }}" class="hover:text-[#C9A227]">Finances et dettes</a>
    @if(in_array(session('user.role'), ['admin_central', 'admin']))
    <a href="{{ route('organisations.index') }}" class="hover:text-[#C9A227]">Espaces</a>
    <a href="{{ route('users.index') }}" class="hover:text-[#C9A227]">Utilisateurs</a>
    @endif
    <span class="ml-auto text-sm opacity-80">{{ session('user.name') }} ({{ session('user.role') }})</span>
    <form method="POST" action="{{ route('logout') }}">@csrf
      <button class="text-sm bg-white/10 px-3 py-1 rounded hover:bg-white/20">Déconnexion</button>
    </form>
    @else
    <a href="{{ route('login') }}" class="ml-auto text-sm bg-white/10 px-3 py-1 rounded hover:bg-white/20">Connexion</a>
    @endif
  </div>
</nav>
<main class="max-w-6xl mx-auto p-4">
  @if(session('ok'))<div class="mb-4 p-3 rounded bg-green-100 text-green-800">{{ session('ok') }}</div>@endif
  @if(session('error'))<div class="mb-4 p-3 rounded bg-red-100 text-red-800">{{ session('error') }}</div>@endif
  @if($errors->any())<div class="mb-4 p-3 rounded bg-red-100 text-red-800"><ul class="list-disc ml-5">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul></div>@endif
  @yield('content')
</main>
</body>
</html>
