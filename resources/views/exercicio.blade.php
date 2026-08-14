@extends('layouts.app')

@section('titulo', $exercicio->titulo)

@section('conteudo')
{{-- Tres areas lado a lado: o que estudar, onde programar, e o avatar. Cada
     uma rola sozinha, para o feedback nunca empurrar o VLibras para fora da
     tela. Abaixo de lg elas viram uma coluna so. --}}
<div class="mx-auto max-w-[1600px] px-4 py-4">

    <div class="mb-4 flex items-center justify-between gap-4">
        <a href="{{ route('trilha') }}" class="clay-btn px-4 py-1.5 text-sm">&larr; Trilha</a>
        <h1 class="truncate text-lg font-bold">{{ $exercicio->titulo }}</h1>
        <span class="clay-selo bg-lilas px-4 py-1.5 text-xs font-semibold capitalize text-white">
            {{ $exercicio->conceito }}
        </span>
    </div>

    <div class="grid gap-4 lg:h-[calc(100vh-8.5rem)] lg:grid-cols-[1fr_1.1fr_340px]">

        {{-- AREA 1: conteudo e enunciado --}}
        <section class="clay flex min-h-0 flex-col p-4">
            <div class="mb-3 flex shrink-0 items-center gap-2">
                <button type="button" data-aba="conteudo" class="aba clay-btn px-4 py-1.5 text-sm">Conteudo</button>
                <button type="button" data-aba="enunciado" class="aba clay-btn px-4 py-1.5 text-sm">Exercicio</button>
            </div>

            <div class="min-h-0 flex-1 overflow-y-auto pr-1">

                <div data-painel="conteudo">
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <h2 class="text-sm font-bold uppercase tracking-wide text-argila-tinta-fraca">
                            Aprenda primeiro
                        </h2>
                        <button type="button" data-bloco="conteudo"
                                class="btn-traduzir clay-btn clay-btn-lilas shrink-0 px-4 py-1 text-xs">
                            Traduzir
                        </button>
                    </div>

                    <div class="bloco-traduzivel space-y-1.5 leading-relaxed" data-bloco="conteudo">
                        @forelse ($segmentosConteudo as $i => $segmento)
                            <p><span class="segmento cursor-pointer"
                                     data-indice="{{ $i }}"
                                     data-texto="{{ $segmento['sinal'] }}">{{ $segmento['texto'] }}</span></p>
                        @empty
                            <p class="text-sm text-argila-tinta-fraca">Este exercicio ainda nao tem conteudo.</p>
                        @endforelse
                    </div>

                    @if ($exercicio->exemplo)
                        <h3 class="mb-2 mt-5 text-sm font-bold uppercase tracking-wide text-argila-tinta-fraca">
                            Exemplo
                        </h3>
                        <pre class="clay-cava overflow-x-auto bg-argila-fundo p-4 font-mono text-xs leading-relaxed">{{ $exercicio->exemplo }}</pre>
                    @endif

                    {{-- Glossario: o termo tecnico fica na tela em ingles, que e
                         como ele aparece no codigo, e a explicacao ao lado e que
                         vai para o avatar. --}}
                    @if ($exercicio->glossario)
                        <h3 class="mb-2 mt-5 text-sm font-bold uppercase tracking-wide text-argila-tinta-fraca">
                            Palavras novas
                        </h3>

                        <div class="bloco-traduzivel space-y-2" data-bloco="glossario">
                            @foreach ($exercicio->glossario as $i => $item)
                                <div class="clay-cava flex items-start gap-3 p-3">
                                    <code class="clay-selo shrink-0 bg-lilas px-3 py-1 font-mono text-xs text-white">{{ $item['termo'] }}</code>
                                    <span class="segmento cursor-pointer text-sm leading-relaxed"
                                          data-indice="{{ $i }}"
                                          data-texto="{{ $item['explicacao'] }}">{{ $item['explicacao'] }}</span>
                                </div>
                            @endforeach
                        </div>
                    @endif
                </div>

                <div data-painel="enunciado" hidden>
                    <div class="mb-3 flex items-center justify-between gap-3">
                        <h2 class="text-sm font-bold uppercase tracking-wide text-argila-tinta-fraca">
                            O que voce deve fazer
                        </h2>
                        <button type="button" data-bloco="enunciado"
                                class="btn-traduzir clay-btn clay-btn-lilas shrink-0 px-4 py-1 text-xs">
                            Traduzir
                        </button>
                    </div>

                    <div class="bloco-traduzivel space-y-1.5 leading-relaxed" data-bloco="enunciado">
                        @foreach ($segmentos as $i => $segmento)
                            <p><span class="segmento cursor-pointer"
                                     data-indice="{{ $i }}"
                                     data-texto="{{ $segmento['sinal'] }}">{{ $segmento['texto'] }}</span></p>
                        @endforeach
                    </div>

                    @if ($exercicio->exemplo_execucao)
                        <h3 class="mb-2 mt-5 text-sm font-bold uppercase tracking-wide text-argila-tinta-fraca">
                            Como deve funcionar
                        </h3>
                        <pre class="clay-cava overflow-x-auto bg-argila-fundo p-4 font-mono text-xs leading-relaxed">{{ $exercicio->exemplo_execucao }}</pre>
                    @endif
                </div>

            </div>
        </section>

        {{-- AREA 2: editor e resultado --}}
        <section class="flex min-h-0 flex-col gap-4">
            <div class="clay flex min-h-0 flex-col p-4">
                <div class="mb-3 flex shrink-0 items-center justify-between">
                    <h2 class="text-sm font-bold uppercase tracking-wide text-argila-tinta-fraca">Seu codigo</h2>
                    <button type="button" id="btn-submeter"
                            data-url="{{ route('submeter', $exercicio) }}"
                            class="clay-btn clay-btn-menta px-6 py-2 text-sm">
                        Submeter
                    </button>
                </div>

                <div id="editor" class="clay-cava h-72 min-h-0 overflow-hidden lg:h-auto lg:flex-1"
                     data-codigo="{{ $exercicio->codigo_inicial }}"></div>
            </div>

            <div id="painel-resultado" class="min-h-0 shrink-0 space-y-3 overflow-y-auto pr-1 lg:max-h-[45%]"></div>
        </section>

        {{-- AREA 3: o avatar, sempre visivel --}}
        <section class="clay flex min-h-0 flex-col p-4">
            <h2 class="mb-3 shrink-0 text-sm font-bold uppercase tracking-wide text-argila-tinta-fraca">
                Traducao em Libras
            </h2>

            {{-- O widget nasce aqui dentro. Mover o DOM depois quebra as
                 referencias internas do plugin. --}}
            <div id="area-vlibras" class="clay-cava shrink-0 bg-argila-fundo">
                <div vw class="enabled">
                    <div vw-access-button class="active"></div>
                    <div vw-plugin-wrapper>
                        <div class="vw-plugin-top-wrapper"></div>
                    </div>
                </div>
            </div>

            <div id="controles-vlibras" class="mt-4 hidden shrink-0 flex-wrap items-center justify-center gap-2">
                <button type="button" data-acao="anterior" class="clay-btn px-4 py-2 text-sm">Anterior</button>
                <button type="button" data-acao="repetir" class="clay-btn clay-btn-pessego px-4 py-2 text-sm">Repetir</button>
                <button type="button" data-acao="proximo" class="clay-btn px-4 py-2 text-sm">Proximo</button>
                <span id="posicao-segmento" class="w-full text-center text-xs font-semibold text-argila-tinta-fraca"></span>
            </div>

            {{-- Mostra o sinal que o avatar executa neste instante. Deixa
                 visivel o que ele entendeu da frase. --}}
            <p id="sinal-atual" class="mt-3 min-h-5 text-center font-mono text-xs font-semibold text-lilas-forte"></p>

            <p class="mt-4 text-xs leading-relaxed text-argila-tinta-fraca">
                Clique em qualquer frase da tela para ver o sinal dela.
            </p>
        </section>

    </div>
</div>

@push('scripts')
    <script src="https://vlibras.gov.br/app/vlibras-plugin.js"></script>
    <script>
        new window.VLibras.Widget('https://vlibras.gov.br/app');
    </script>
@endpush
@endsection
