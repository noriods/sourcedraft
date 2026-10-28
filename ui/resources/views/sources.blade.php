@foreach ($ids as $id)
  <details class="mt-1 block rounded-md bg-sky-50 px-2 py-1 text-xs text-sky-900 ring-1 ring-sky-100">
    <summary class="cursor-pointer font-mono">{{ $id }}</summary>
    <p class="mt-1 whitespace-pre-line text-slate-600">{{ \Illuminate\Support\Str::limit($chunks[$id]['text'] ?? 'Source text not found.', 600) }}</p>
  </details>
@endforeach
