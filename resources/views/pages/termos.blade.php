@extends('layouts.simple', ['title' => 'Termos de Uso'])

@section('content')
    <main class="mx-auto max-w-3xl px-6 py-10">
        <a href="{{ url('/') }}" class="text-sm text-gray-600 underline-offset-4 hover:underline">Voltar</a>
        <h1 class="mt-4 text-2xl font-semibold">Termos de Uso</h1>
        <article class="prose prose-zinc mt-6 max-w-none">
            {!! $page?->content !!}
        </article>
    </main>
@endsection

