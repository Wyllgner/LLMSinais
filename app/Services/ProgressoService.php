<?php

namespace App\Services;

use App\Models\Attempt;
use App\Models\Exercise;
use Illuminate\Support\Facades\DB;

/**
 * Acompanha o desempenho do aluno ao longo da trilha. Alimenta o painel e
 * tambem o contexto que vai para a LLM.
 */
class ProgressoService
{
    public function __construct(private ClassificadorErro $classificador) {}

    public function resumo(string $sessaoUuid): array
    {
        $tentativas = Attempt::where('sessao_uuid', $sessaoUuid);

        $total = (clone $tentativas)->count();
        $acertos = (clone $tentativas)->where('passou', true)->count();

        return [
            'tentativas' => $total,
            'exercicios_concluidos' => (clone $tentativas)->where('passou', true)
                ->distinct('exercise_id')->count('exercise_id'),
            'exercicios_totais' => Exercise::count(),
            'taxa_acerto' => $total ? round($acertos / $total * 100) : 0,
            'erros_recorrentes' => $this->errosRecorrentes($sessaoUuid),
            'proximo' => $this->proximoExercicio($sessaoUuid),
        ];
    }

    /**
     * @return array<int, array{tipo: string, rotulo: string, total: int}>
     */
    public function errosRecorrentes(string $sessaoUuid, int $limite = 3): array
    {
        return Attempt::where('sessao_uuid', $sessaoUuid)
            ->whereNotNull('tipo_erro')
            ->whereNot('tipo_erro', 'sem_erro')
            ->select('tipo_erro', DB::raw('count(*) as total'))
            ->groupBy('tipo_erro')
            ->orderByDesc('total')
            ->limit($limite)
            ->get()
            ->map(fn ($linha) => [
                'tipo' => $linha->tipo_erro,
                'rotulo' => $this->classificador->rotuloHumano($linha->tipo_erro),
                'total' => (int) $linha->total,
            ])
            ->all();
    }

    /**
     * O primeiro exercicio da ordem que o aluno ainda nao concluiu.
     */
    public function proximoExercicio(string $sessaoUuid): ?Exercise
    {
        $concluidos = Attempt::where('sessao_uuid', $sessaoUuid)
            ->where('passou', true)
            ->pluck('exercise_id')
            ->unique();

        return Exercise::whereNotIn('id', $concluidos)->orderBy('ordem')->first();
    }
}
