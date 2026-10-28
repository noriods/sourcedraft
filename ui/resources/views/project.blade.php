@extends('layouts.app')
@section('body')
@php $badge = ['draft' => 'bg-amber-100 text-amber-800', 'final' => 'bg-emerald-100 text-emerald-800', 'failed' => 'bg-red-100 text-red-800', 'approved' => 'bg-emerald-100 text-emerald-800'] @endphp
<p class="text-sm text-slate-500"><a href="/" class="hover:underline">Projects</a> / {{ $p }}</p>
<h1 class="mb-1 mt-1 text-2xl font-semibold">{{ $p }}</h1>
<p class="mb-8 text-sm text-slate-500">Model: {{ $cfg['model'] }} · Privacy: {{ $cfg['privacy'] }}</p>

<div class="grid gap-6 lg:grid-cols-2">
  <section class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
    <h2 class="mb-1 text-lg font-semibold">1. Your documents</h2>
    <p class="mb-3 text-sm text-slate-500">PowerPoint, PDF, Word or text files.</p>
    <ul class="mb-4 divide-y divide-slate-100 text-sm">
      @foreach ($sources as $s) <li class="py-1.5">{{ $s }}</li> @endforeach
    </ul>
    <form method="post" action="/projects/{{ $p }}/upload" enctype="multipart/form-data" class="mb-4 flex items-center gap-3">
      @csrf
      <input type="file" name="file" required class="text-sm">
      <button class="rounded-lg border border-slate-300 px-3 py-1.5 text-sm font-medium hover:bg-slate-50">Upload</button>
    </form>
    <form method="post" action="/projects/{{ $p }}/ingest" class="flex items-center gap-3">
      @csrf
      <button class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">Ingest</button>
      <span class="text-sm text-slate-500">{{ $chunks === null ? 'Not read yet.' : "$chunks pieces indexed." }}</span>
    </form>
  </section>

  <section class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
    <h2 class="mb-1 text-lg font-semibold">2. Make a draft</h2>
    <p class="mb-3 text-sm text-slate-500">It writes only from your documents and cites every point.</p>
    <form method="post" action="/projects/{{ $p }}/draft" class="space-y-3">
      @csrf
      <label class="block text-sm">What to make
        <select name="recipe" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2">
          @foreach ($cfg['recipes'] as $r) <option value="{{ $r }}">{{ ['teaching-deck' => 'Teaching deck (slides + instructor notes)', 'case-vignette' => 'Case vignette (student case + teaching note)'][$r] ?? $r }}</option> @endforeach
        </select></label>
      <label class="block text-sm">Topic
        <input name="topic" required placeholder="e.g. Managerial decision making" class="mt-1 w-full rounded-lg border border-slate-300 px-3 py-2"></label>
      <button class="rounded-lg bg-slate-900 px-4 py-2 text-sm font-medium text-white hover:bg-slate-700">Draft</button>
    </form>
    @foreach ($jobs as $j)
      <p class="mt-4 rounded-lg px-3 py-2 text-sm ring-1 {{ $j->pending() ? 'bg-sky-50 text-sky-800 ring-sky-200' : ($j->status === 'failed' ? 'bg-red-50 text-red-800 ring-red-200' : 'bg-slate-50 ring-slate-200') }}">
        {{ $j->topic }}:
        @if ($j->pending()) {{ $j->status === 'queued' ? 'waiting to start…' : 'writing the draft…' }}
        @elseif ($j->status === 'failed') failed. {{ $j->error }} @if ($j->run) <a class="underline" href="/projects/{{ $p }}/runs/{{ $j->run }}">Open</a> @endif
        @else done. <a class="underline" href="/projects/{{ $p }}/runs/{{ $j->run }}">Review it</a> @endif
      </p>
    @endforeach
  </section>
</div>

<section class="mt-6 rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
  <h2 class="mb-3 text-lg font-semibold">3. Drafts to review</h2>
  <table class="w-full text-left text-sm">
    @forelse ($runs as $r)
      <tr class="border-t border-slate-100">
        <td class="py-2"><a class="font-medium hover:underline" href="/projects/{{ $p }}/runs/{{ $r['id'] }}">{{ $r['topic'] }}</a></td>
        <td class="text-slate-500">{{ $r['recipe'] }}</td>
        <td class="text-right"><span class="rounded-full px-2.5 py-0.5 text-xs font-medium {{ $badge[$r['status']] ?? 'bg-slate-100' }}">{{ $r['status'] === 'final' ? 'approved' : $r['status'] }}</span></td>
      </tr>
    @empty
      <tr><td class="py-2 text-slate-500">Nothing yet.</td></tr>
    @endforelse
  </table>
</section>
@endsection
