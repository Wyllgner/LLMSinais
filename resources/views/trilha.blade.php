@extends('layouts.app')

@section('titulo', 'Trilha de Programacao I')

@section('conteudo')
<div class="mx-auto max-w-2xl px-4 py-10">

    <h1 class="mb-2 text-2xl font-semibold">Trilha de Programacao I</h1>
    <p class="mb-8 text-slate-600">Siga os niveis em ordem. Cada nivel abre quando voce termina o anterior.</p>

    @if ($progresso['tentativas'] > 0)
        <section class="mb-10 rounded-lg border border-slate-200 bg-white p-5">
            <h2 class="mb-4 text-sm font-semibold uppercase tracking-wide text-slate-500">Seu progresso</h2>

            <div class="mb-5 grid grid-cols-3 gap-4 text-center">
                <div>
                    <p class="text-2xl font-semibold text-slate-900">
                        {{ $progresso['exercicios_concluidos'] }}<span class="text-base text-slate-400">/{{ $progresso['exercicios_totais'] }}</span>
                    </p>
                    <p class="text-xs text-slate-500">exercicios</p>
                </div>
                <div>
                    <p class="text-2xl font-semibold text-slate-900">{{ $progresso['tentativas'] }}</p>
                    <p class="text-xs text-slate-500">tentativas</p>
                </div>
                <div>
                    <p class="text-2xl font-semibold text-slate-900">{{ $progresso['taxa_acerto'] }}%</p>
                    <p class="text-xs text-slate-500">de acerto</p>
                </div>
            </div>

            @if (count($progresso['erros_recorrentes']))
                @php $maior = $progresso['erros_recorrentes'][0]['total']; @endphp

                <h3 class="mb-2 text-xs font-semibold uppercase tracking-wide text-slate-500">Onde voce mais erra</h3>
                <ul class="space-y-2">
                    @foreach ($progresso['erros_recorrentes'] as $erro)
                        <li class="flex items-center gap-3 text-sm">
                            <span class="w-44 shrink-0 text-slate-700">{{ $erro['rotulo'] }}</span>
                            <span class="h-2 flex-1 rounded-full bg-slate-100">
                                <span class="block h-2 rounded-full bg-amber-400"
                                      style="width: {{ round($erro['total'] / $maior * 100) }}%"></span>
                            </span>
                            <span class="w-8 text-right text-slate-500">{{ $erro['total'] }}x</span>
                        </li>
                    @endforeach
                </ul>
            @endif

            @if ($progresso['proximo'])
                <p class="mt-5 text-sm text-slate-600">
                    Continue em
                    <a href="{{ route('exercicio', $progresso['proximo']) }}"
                       class="font-medium text-sky-700 underline underline-offset-4">{{ $progresso['proximo']->titulo }}</a>.
                </p>
            @else
                <p class="mt-5 text-sm font-medium text-emerald-700">Voce concluiu todos os exercicios da trilha.</p>
            @endif
        </section>
    @endif

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
