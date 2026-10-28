@extends('layouts.app')
@section('body')
<h1 class="mb-6 text-2xl font-semibold">Projects</h1>
<div class="grid gap-4 sm:grid-cols-2">
  @forelse ($projects as $p)
    <a href="/projects/{{ $p }}" class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200 hover:ring-slate-400">
      <p class="text-lg font-medium">{{ $p }}</p>
      <p class="mt-1 text-sm text-slate-500">Open to add documents and draft</p>
    </a>
  @empty
    <p class="text-slate-500">No projects yet. Add a folder under projects/.</p>
  @endforelse
</div>
@endsection
