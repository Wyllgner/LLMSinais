@extends('layouts.app')

@section('titulo', 'Trilha de Programação I')

@section('conteudo')
<div class="mx-auto max-w-3xl px-4 py-10">

    <h1 class="mb-2 text-3xl font-bold tracking-tight">Trilha de Programação I</h1>
    <p class="mb-8 text-argila-tinta-fraca">
        Siga os níveis em ordem. Cada nível abre quando você termina o anterior.
    </p>

    @if ($progresso['tentativas'] > 0)
        <section class="clay mb-10 p-7">
            <h2 class="mb-5 text-sm font-bold uppercase tracking-wide text-argila-tinta-fraca">Seu progresso</h2>

            <div class="mb-6 grid grid-cols-3 gap-4">
                @php
                    $numeros = [
                        ['valor' => $progresso['exercicios_concluidos'].'/'.$progresso['exercicios_totais'], 'rotulo' => 'exercícios', 'cor' => 'bg-menta text-menta-forte'],
                        ['valor' => $progresso['tentativas'], 'rotulo' => 'tentativas', 'cor' => 'bg-lilas text-white'],
                        ['valor' => $progresso['taxa_acerto'].'%', 'rotulo' => 'de acerto', 'cor' => 'bg-pessego text-pessego-forte'],
                    ];
                @endphp

                @foreach ($numeros as $numero)
                    <div class="clay-cava flex flex-col items-center gap-2 px-2 py-4">
                        <span class="clay-selo flex h-14 w-14 items-center justify-center text-lg font-bold {{ $numero['cor'] }}">
                            {{ $numero['valor'] }}
                        </span>
                        <span class="text-xs font-medium text-argila-tinta-fraca">{{ $numero['rotulo'] }}</span>
                    </div>
                @endforeach
            </div>

            @if (count($progresso['erros_recorrentes']))
                @php $maior = $progresso['erros_recorrentes'][0]['total']; @endphp

                <h3 class="mb-3 text-xs font-bold uppercase tracking-wide text-argila-tinta-fraca">
                    Onde você mais erra
                </h3>
                <ul class="space-y-3">
                    @foreach ($progresso['erros_recorrentes'] as $erro)
                        <li class="flex items-center gap-3 text-sm">
                            <span class="w-44 shrink-0 font-medium">{{ $erro['rotulo'] }}</span>
                            <span class="clay-cava h-4 flex-1 overflow-hidden">
                                <span class="block h-4 rounded-full bg-rosa"
                                      style="width: {{ round($erro['total'] / $maior * 100) }}%"></span>
                            </span>
                            <span class="w-9 text-right font-semibold text-argila-tinta-fraca">{{ $erro['total'] }}x</span>
                        </li>
                    @endforeach
                </ul>
            @endif

            @if ($progresso['proximo'])
                <a href="{{ route('exercicio', $progresso['proximo']) }}"
                   class="clay-btn clay-btn-lilas mt-6 inline-block px-6 py-2.5 text-sm">
                    Continuar em {{ $progresso['proximo']->titulo }}
                </a>
            @else
                <p class="clay-cava mt-6 px-5 py-3 text-sm font-semibold text-menta-forte">
                    Você concluiu todos os exercícios da trilha.
                </p>
            @endif
        </section>
    @endif

    <ol class="relative space-y-5">
        @foreach ($exercicios as $exercicio)
            @php
                $selo = $exercicio->concluido
                    ? 'bg-menta text-menta-forte'
                    : ($exercicio->liberado ? 'bg-lilas text-white' : 'bg-argila-face text-argila-tinta-fraca');
            @endphp

            <li class="clay flex items-center gap-5 p-5 {{ $exercicio->liberado ? '' : 'opacity-60' }}">

                <span class="clay-selo flex h-16 w-16 shrink-0 items-center justify-center text-xl font-bold {{ $selo }}">
                    {{ $exercicio->concluido ? '✓' : $exercicio->ordem }}
                </span>

                <div class="min-w-0 flex-1">
                    <p class="text-lg font-bold">{{ $exercicio->titulo }}</p>
                    <p class="mt-0.5 text-sm capitalize text-argila-tinta-fraca">{{ $exercicio->conceito }}</p>
                </div>

                @if ($exercicio->liberado)
                    <a href="{{ route('exercicio', $exercicio) }}"
                       class="clay-btn shrink-0 px-5 py-2 text-sm {{ $exercicio->concluido ? '' : 'clay-btn-lilas' }}">
                        {{ $exercicio->concluido ? 'Refazer' : 'Começar' }}
                    </a>
                @else
                    <span class="clay-cava shrink-0 px-4 py-2 text-xs font-semibold text-argila-tinta-fraca">
                        Bloqueado
                    </span>
                @endif

            </li>
        @endforeach
    </ol>

</div>
@endsection
