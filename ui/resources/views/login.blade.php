@extends('layouts.app')
@section('body')
<form method="post" action="/login" class="mx-auto mt-10 max-w-sm space-y-4 rounded-xl bg-white p-8 shadow-sm ring-1 ring-slate-200">
  @csrf
  <h1 class="text-xl font-semibold">Sign in</h1>
  <label class="block text-sm">Email<input name="email" type="email" value="{{ old('email') }}" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"></label>
  <label class="block text-sm">Password<input name="password" type="password" required class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"></label>
  <button class="w-full rounded-lg bg-slate-900 px-4 py-2 font-medium text-white hover:bg-slate-700">Sign in</button>
</form>
@endsection
