@extends('layouts.app')

@section('titulo', $exercicio->titulo)

@section('conteudo')
<div class="mx-auto max-w-6xl px-4 py-6">

    <a href="{{ route('trilha') }}" class="mb-4 inline-block text-sm text-slate-500 hover:text-slate-800">
        &larr; Voltar para a trilha
    </a>

    <div class="grid gap-6 lg:grid-cols-2">

        <section class="rounded-lg border border-slate-200 bg-white p-5">
            <div class="mb-3 flex items-center justify-between">
                <h1 class="text-xl font-semibold">{{ $exercicio->titulo }}</h1>
                <button type="button"
                        class="btn-traduzir rounded-md bg-sky-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-sky-700"
                        data-bloco="enunciado">
                    Traduzir com VLibras
                </button>
            </div>

            <div class="bloco-traduzivel space-y-1 leading-relaxed" data-bloco="enunciado">
                @foreach ($segmentos as $i => $segmento)
                    <p><span class="segmento cursor-pointer rounded px-1 transition-colors"
                             data-indice="{{ $i }}"
                             data-texto="{{ $segmento }}">{{ $segmento }}</span></p>
                @endforeach
            </div>
        </section>

        <section class="space-y-6">
            <div class="rounded-lg border border-slate-200 bg-white p-5">
                <h2 class="mb-3 text-sm font-semibold uppercase tracking-wide text-slate-500">Seu codigo</h2>
                <textarea id="editor"
                          class="h-64 w-full rounded-md border border-slate-300 bg-slate-900 p-3 font-mono text-sm text-slate-100 focus:border-sky-500 focus:outline-none"
                          spellcheck="false">{{ $exercicio->codigo_inicial }}</textarea>
                <div class="mt-3 flex justify-end">
                    <button type="button" id="btn-submeter"
                            data-url="{{ route('submeter', $exercicio) }}"
                            class="rounded-md bg-emerald-600 px-4 py-2 text-sm font-medium text-white hover:bg-emerald-700 disabled:opacity-60">
                        Submeter
                    </button>
                </div>
            </div>

            <div id="painel-resultado" class="space-y-4"></div>

            <div id="area-vlibras" class="rounded-lg border border-dashed border-slate-300 bg-white p-5 text-center text-sm text-slate-500">
                O avatar do VLibras aparece aqui.
            </div>
        </section>

    </div>
</div>
@endsection
