@extends('layouts.app')
@section('body')
@php
  $final = ($status['status'] ?? '') === 'final';
@endphp
<p class="text-sm text-slate-500"><a href="/" class="hover:underline">Projects</a> / <a href="/projects/{{ $p }}" class="hover:underline">{{ $p }}</a> / review</p>
<div class="mb-6 mt-1 flex flex-wrap items-center gap-3">
  <h1 class="text-2xl font-semibold">{{ $model['title'] ?? $manifest['topic'] }}</h1>
  <span class="rounded-full px-3 py-1 text-xs font-medium {{ $final ? 'bg-emerald-100 text-emerald-800' : ($status['status'] === 'failed' ? 'bg-red-100 text-red-800' : 'bg-amber-100 text-amber-800') }}">{{ $final ? 'Approved' : ucfirst($status['status']) }}</span>
</div>

<div class="mb-6 rounded-lg px-4 py-3 text-sm ring-1 {{ $valid['ok'] ?? false ? 'bg-emerald-50 text-emerald-800 ring-emerald-200' : 'bg-red-50 text-red-800 ring-red-200' }}">
  @if ($valid['ok'] ?? false) Citation check passed: every point names a source that exists in your documents.
  @else Citation check failed:
    <ul class="mt-1 list-disc pl-5">@foreach ($valid['errors'] ?? [] as $e) <li>{{ $e }}</li> @endforeach</ul>
  @endif
</div>

@if (!empty($model['slides']))
  @foreach ($model['slides'] as $i => $s)
    <section class="mb-6 grid gap-6 rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200 lg:grid-cols-5">
      <div class="lg:col-span-2">
        <p class="mb-2 text-xs font-medium uppercase tracking-wide text-slate-400">Slide {{ $i + 1 }}</p>
        @if (isset($images[$i]))
          <img class="w-full rounded-lg ring-1 ring-slate-200" alt="Slide {{ $i + 1 }}" src="/projects/{{ $p }}/runs/{{ $run }}/slide/{{ $imgDir }}/{{ $images[$i] }}">
        @endif
      </div>
      <div class="space-y-4 text-sm lg:col-span-3">
        <div><h2 class="mb-1 font-semibold">Student slide: {{ $s['title'] }}</h2>
          <ul class="list-disc space-y-2 pl-5">@foreach ($s['points'] ?? [] as $c) <li>{{ $c['text'] }} @include('sources', ['ids' => $c['sources'] ?? []])</li> @endforeach</ul></div>
        <div><h2 class="mb-1 font-semibold">Instructor notes</h2>
          <ul class="list-disc space-y-2 pl-5 text-slate-600">@foreach ($s['notes'] ?? [] as $c) <li>{{ $c['text'] }} @include('sources', ['ids' => $c['sources'] ?? []])</li> @endforeach</ul></div>
      </div>
    </section>
  @endforeach
@elseif ($model)
  <section class="mb-6 space-y-6 rounded-xl bg-white p-6 text-sm shadow-sm ring-1 ring-slate-200">
    @foreach ($model as $key => $val)
      @continue($key === 'title')
      <div><h2 class="mb-1 font-semibold">{{ ucfirst(str_replace('_', ' ', $key)) }}</h2>
        @foreach ((isset($val['text']) ? [$val] : $val) as $c)
          <p class="mb-2">{{ is_array($c) ? $c['text'] : $c }} @if (is_array($c)) @include('sources', ['ids' => $c['sources'] ?? []]) @endif</p>
        @endforeach</div>
    @endforeach
  </section>
@endif

<section class="rounded-xl bg-white p-6 shadow-sm ring-1 ring-slate-200">
  @if ($final)
    <h2 class="mb-1 text-lg font-semibold">Approved</h2>
    <p class="mb-4 text-sm text-slate-600">By {{ $status['approval']['by'] }} on {{ $status['approval']['at'] }}. The clean files have no DRAFT watermark.</p>
  @elseif ($valid['ok'] ?? false)
    <h2 class="mb-1 text-lg font-semibold">Approve</h2>
    <p class="mb-4 text-sm text-slate-600">The clean file is only produced after a named person approves.</p>
    <form method="post" action="/projects/{{ $p }}/runs/{{ $run }}/approve" class="mb-4 flex flex-wrap items-center gap-4">
      @csrf
      <input name="by" placeholder="Your full name" class="w-64 rounded-lg border border-slate-300 px-3 py-2 text-sm">
      <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="confirm" value="1"> I have reviewed this and approve it.</label>
      <button class="rounded-lg bg-emerald-700 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-600">Approve and make final</button>
    </form>
  @endif
  <h3 class="mb-2 text-sm font-semibold">Downloads</h3>
  <ul class="space-y-1 text-sm">
    @foreach ($files as $f)
      <li><a class="text-sky-700 underline" href="/projects/{{ $p }}/runs/{{ $run }}/file/{{ $f }}">{{ $f }}</a>{{ str_starts_with($f, 'draft') ? ' (DRAFT watermark)' : '' }}</li>
    @endforeach
  </ul>
</section>
@endsection
