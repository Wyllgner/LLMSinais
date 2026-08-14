<?php

namespace App\Console\Commands;

use App\Models\FeedbackLevel;
use App\Models\MedicaoTraducao;
use App\Services\LexicoSinais;
use App\Services\SinalizabilidadeService;
use Illuminate\Console\Command;

/**
 * Os numeros que o artigo reporta. Duas visoes que se validam:
 * o indice previsto pelo verificador, e o que o avatar de fato fez.
 */
class RelatorioSinalizabilidade extends Command
{
    protected $signature = 'relatorio:sinalizabilidade';

    protected $description = 'Indice de sinalizabilidade das dicas e datilologia medida no avatar';

    public function handle(SinalizabilidadeService $verificador, LexicoSinais $lexico): int
    {
        $this->previsto();
        $this->medido();
        $this->cadeia($verificador);

        $this->newLine();
        $this->line('Lexico de termos sem sinal: '.count($lexico->termos()).' termos.');

        return self::SUCCESS;
    }

    /**
     * O que o portao julgou, dica a dica, no momento da geracao.
     */
    private function previsto(): void
    {
        $dicas = FeedbackLevel::whereNotNull('sinalizabilidade')->get();

        $this->info('Indice previsto pelo verificador, por nivel de dica');

        if ($dicas->isEmpty()) {
            $this->line('  Nenhuma dica avaliada ainda. Gere dicas e rode de novo.');

            return;
        }

        $linhas = $dicas->groupBy('nivel')->sortKeys()->map(fn ($grupo, $nivel) => [
            'nivel' => $nivel,
            'dicas' => $grupo->count(),
            'indice medio' => round($grupo->avg('sinalizabilidade'), 2),
            'aprovadas' => $grupo->where('sinalizabilidade', 1.0)->count(),
        ])->values();

        $this->table(['nivel', 'dicas', 'indice medio', 'aprovadas'], $linhas);
    }

    /**
     * O que o avatar fez de verdade, capturado em execucao.
     */
    private function medido(): void
    {
        $this->newLine();
        $this->info('Datilologia medida no avatar, por origem do texto');

        $medicoes = MedicaoTraducao::all();

        if ($medicoes->isEmpty()) {
            $this->line('  Nenhuma traducao medida ainda. Use a aplicacao e rode de novo.');

            return;
        }

        $linhas = $medicoes->groupBy(fn ($m) => $this->familia($m->origem))
            ->map(function ($grupo, $origem) {
                $comSoletracao = $grupo->filter(fn ($m) => ! empty($m->soletrados));
                $sinais = $grupo->sum('sinais');
                $soletrados = $grupo->sum(fn ($m) => count($m->soletrados ?? []));

                return [
                    'origem' => $origem,
                    'frases' => $grupo->count(),
                    'sinais' => $sinais,
                    'soletrados' => $soletrados,
                    '% dos sinais' => $sinais ? round($soletrados / $sinais * 100, 1).'%' : '—',
                    'frases afetadas' => $comSoletracao->count(),
                ];
            })->values();

        $this->table(
            ['origem', 'frases', 'sinais', 'soletrados', '% dos sinais', 'frases afetadas'],
            $linhas
        );

        $termos = $medicoes->pluck('soletrados')->flatten()->filter()->countBy()->sortDesc()->take(10);

        if ($termos->isNotEmpty()) {
            $this->newLine();
            $this->info('Termos mais soletrados');
            $this->table(
                ['termo', 'vezes'],
                $termos->map(fn ($n, $t) => ['termo' => $t, 'vezes' => $n])->values()
            );
        }
    }

    /**
     * A comparacao que sustenta a contribuicao: a mesma informacao, antes e
     * depois de passar pela cadeia de simplificacao.
     */
    private function cadeia(SinalizabilidadeService $verificador): void
    {
        $this->newLine();
        $this->info('Cadeia de simplificacao, indice previsto');

        $tentativas = \App\Models\Attempt::whereNotNull('stderr_bruto')
            ->whereNotNull('erro_simplificado')
            ->get();

        if ($tentativas->isEmpty()) {
            $this->line('  Nenhuma tentativa com erro bruto e erro simplificado.');

            return;
        }

        $bruto = $tentativas->avg(fn ($t) => $verificador->avaliar($t->stderr_bruto)['indice']);
        $simples = $tentativas->avg(fn ($t) => $verificador->avaliar($t->erro_simplificado)['indice']);

        $this->table(['etapa', 'indice medio'], [
            ['etapa' => 'mensagem do interpretador', 'indice medio' => round($bruto, 2)],
            ['etapa' => 'erro simplificado', 'indice medio' => round($simples, 2)],
        ]);

        $this->line('  Base: '.$tentativas->count().' tentativas.');
    }

    /**
     * As dicas chegam como dica-1, dica-2… e interessam agrupadas.
     */
    private function familia(string $origem): string
    {
        return str_starts_with($origem, 'dica-') ? 'dica' : $origem;
    }
}
