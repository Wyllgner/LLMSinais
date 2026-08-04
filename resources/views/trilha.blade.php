@extends('layouts.app')

@section('titulo', 'Trilha de Programacao I')

@section('conteudo')
<div class="mx-auto max-w-2xl px-4 py-10">

    <h1 class="mb-2 text-2xl font-semibold">Trilha de Programacao I</h1>
    <p class="mb-10 text-slate-600">Siga os niveis em ordem. Cada nivel abre quando voce termina o anterior.</p>

    <ol class="relative">
        @foreach ($exercicios as $exercicio)
            <li class="relative flex items-start gap-5 pb-10 last:pb-0">

                @unless ($loop->last)
                    <span class="absolute left-7 top-14 h-full w-0.5 {{ $exercicio->concluido ? 'bg-emerald-400' : 'bg-slate-200' }}"
                          aria-hidden="true"></span>
                @endunless

                @php
                    $classesNo = $exercicio->concluido
                        ? 'bg-emerald-500 text-white ring-emerald-200'
                        : ($exercicio->liberado
                            ? 'bg-white text-slate-700 ring-slate-300'
                            : 'bg-slate-100 text-slate-400 ring-slate-200');
                @endphp

                <span class="z-10 flex h-14 w-14 shrink-0 items-center justify-center rounded-full text-lg font-semibold ring-4 {{ $classesNo }}">
                    {{ $exercicio->concluido ? '✓' : $exercicio->ordem }}
                </span>

                <div class="pt-2">
                    @if ($exercicio->liberado)
                        <a href="{{ route('exercicio', $exercicio) }}"
                           class="text-lg font-medium text-slate-900 underline-offset-4 hover:underline">
                            {{ $exercicio->titulo }}
                        </a>
                    @else
                        <span class="text-lg font-medium text-slate-400">{{ $exercicio->titulo }}</span>
                    @endif
                    <p class="mt-1 text-sm capitalize text-slate-500">{{ $exercicio->conceito }}</p>
                </div>

            </li>
        @endforeach
    </ol>

</div>
@endsection
