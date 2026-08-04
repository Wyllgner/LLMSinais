<?php

namespace App\Console\Commands;

use App\Models\FeedbackLevel;
use Illuminate\Console\Command;

/**
 * Consolida o consumo de tokens das dicas geradas. O custo medio por dica e o
 * numero que o artigo reporta.
 */
class RelatorioCusto extends Command
{
    protected $signature = 'llmsinais:custo';

    protected $description = 'Mostra o consumo de tokens e o custo estimado das dicas geradas';

    public function handle(): int
    {
        $total = FeedbackLevel::selectRaw(
            'count(*) as dicas,
             sum(chamadas) as chamadas,
             sum(tokens_entrada) as entrada,
             sum(tokens_saida) as saida,
             sum(tokens_raciocinio) as raciocinio'
        )->first();

        if (! $total->dicas) {
            $this->warn('Nenhuma dica registrada ainda.');

            return self::SUCCESS;
        }

        $this->info('Consumo acumulado');
        $this->table(['Metrica', 'Valor'], [
            ['Dicas geradas', $total->dicas],
            ['Chamadas a API', $total->chamadas],
            ['Chamadas por dica', round($total->chamadas / $total->dicas, 2)],
            ['Tokens de entrada', number_format((int) $total->entrada)],
            ['Tokens de saida', number_format((int) $total->saida)],
            ['  dos quais raciocinio', number_format((int) $total->raciocinio)],
            ['Tokens por dica', round(($total->entrada + $total->saida) / $total->dicas)],
        ]);

        $this->newLine();
        $this->info('Por nivel de dica');
        $this->table(
            ['Nivel', 'Dicas', 'Entrada', 'Saida', 'Tokens por dica'],
            FeedbackLevel::selectRaw(
                'nivel, count(*) as dicas,
                 sum(tokens_entrada) as entrada,
                 sum(tokens_saida) as saida'
            )->groupBy('nivel')->orderBy('nivel')->get()
                ->map(fn ($l) => [
                    $l->nivel,
                    $l->dicas,
                    number_format((int) $l->entrada),
                    number_format((int) $l->saida),
                    round(($l->entrada + $l->saida) / $l->dicas),
                ])->all()
        );

        $this->mostrarCusto((int) $total->entrada, (int) $total->saida, (int) $total->dicas);

        return self::SUCCESS;
    }

    private function mostrarCusto(int $entrada, int $saida, int $dicas): void
    {
        $precoEntrada = config('llmsinais.openai.preco_entrada');
        $precoSaida = config('llmsinais.openai.preco_saida');

        $this->newLine();

        // Variavel vazia no .env chega como string vazia, nao como null.
        if (blank($precoEntrada) || blank($precoSaida)) {
            $this->line('Custo em dolares: defina OPENAI_PRECO_ENTRADA e OPENAI_PRECO_SAIDA no .env');
            $this->line('com o preco por milhao de tokens do seu modelo, conforme a tabela da OpenAI.');

            return;
        }

        $custo = ($entrada / 1_000_000) * (float) $precoEntrada
               + ($saida / 1_000_000) * (float) $precoSaida;

        $this->info('Custo estimado');
        $this->table(['Metrica', 'Valor'], [
            ['Modelo', config('llmsinais.openai.model')],
            ['Total', '$'.number_format($custo, 4)],
            ['Por dica', '$'.number_format($custo / $dicas, 6)],
            ['Por 1000 dicas', '$'.number_format(($custo / $dicas) * 1000, 2)],
        ]);
    }
}
