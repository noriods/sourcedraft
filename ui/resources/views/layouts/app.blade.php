<!doctype html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title>sourcedraft</title>
  @vite(['resources/css/app.css'])
  @if (!empty($refresh)) <meta http-equiv="refresh" content="3"> @endif
</head>
<body class="bg-slate-50 text-slate-800 antialiased">
<header class="bg-slate-900 text-white">
  <div class="mx-auto flex max-w-6xl items-center justify-between px-6 py-4">
    <a href="/" class="text-lg font-semibold tracking-tight">sourcedraft</a>
    @auth
      <form method="post" action="/logout">@csrf<button class="text-sm text-slate-300 hover:text-white">Sign out</button></form>
    @endauth
  </div>
</header>
<main class="mx-auto max-w-6xl px-6 py-8">
  @if (session('ok')) <p class="mb-6 rounded-lg bg-emerald-50 px-4 py-3 text-emerald-800 ring-1 ring-emerald-200">{{ session('ok') }}</p> @endif
  @if ($errors->any()) <p class="mb-6 rounded-lg bg-red-50 px-4 py-3 text-red-800 ring-1 ring-red-200">{{ $errors->first() }}</p> @endif
  @yield('body')
</main>
</body>
</html>
