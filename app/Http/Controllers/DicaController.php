<?php

namespace App\Http\Controllers;

use App\Models\Attempt;
use App\Models\FeedbackLevel;
use App\Services\FeedbackService;
use App\Services\SegmentadorService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;

class DicaController extends Controller
{
    public function __construct(
        private FeedbackService $feedback,
        private SegmentadorService $segmentador,
    ) {}

    public function proximaDica(Request $request, Attempt $tentativa): JsonResponse
    {
        if ($tentativa->sessao_uuid !== $request->session()->get('sessao_uuid')) {
            throw new AccessDeniedHttpException;
        }

        $maximo = config('llmsinais.feedback.nivel_maximo');

        if ($tentativa->passou) {
            return response()->json(['mensagem' => 'Esta tentativa já passou nos testes.'], 422);
        }

        if ($tentativa->nivel_dica >= $maximo) {
            return response()->json(['mensagem' => 'Você já viu todas as dicas.'], 422);
        }

        $nivel = $tentativa->nivel_dica + 1;

        // Nivel ja gerado volta do banco, sem nova chamada a API.
        $dica = FeedbackLevel::firstWhere(['attempt_id' => $tentativa->id, 'nivel' => $nivel]);

        if (! $dica) {
            $gerada = $this->feedback->gerar($tentativa, $nivel);
            ['texto' => $texto, 'uso' => $uso] = $gerada;

            $dica = FeedbackLevel::create([
                'attempt_id' => $tentativa->id,
                'nivel' => $nivel,
                'texto' => $texto,
                'segmentos' => $this->segmentador->segmentar($texto),
                'tokens_entrada' => $uso['entrada'],
                'tokens_saida' => $uso['saida'],
                'tokens_raciocinio' => $uso['raciocinio'],
                'chamadas' => $uso['chamadas'],
                'sinalizabilidade' => $gerada['sinalizabilidade']['indice'] ?? null,
                'violacoes' => $gerada['sinalizabilidade']['violacoes'] ?? null,
            ]);
        }

        $tentativa->update(['nivel_dica' => $nivel]);

        return response()->json([
            'nivel' => $nivel,
            'texto' => $dica->texto,
            'segmentos' => $dica->segmentos,
            'tem_proxima' => $nivel < $maximo,
        ]);
    }

    public function erroSimplificado(Request $request, Attempt $tentativa): JsonResponse
    {
        if ($tentativa->sessao_uuid !== $request->session()->get('sessao_uuid')) {
            throw new AccessDeniedHttpException;
        }

        // O timeout nao produz stderr, mas e o erro que o aluno mais precisa
        // entender. A frase e fixa: nao ha mensagem do interpretador para
        // simplificar, e o texto ja esta no vocabulario que o avatar sinaliza.
        if (! $tentativa->stderr_bruto && $tentativa->houveTimeout() && ! $tentativa->erro_simplificado) {
            $tentativa->update([
                'erro_simplificado' => config('llmsinais.feedback.erro_timeout'),
            ]);
        }

        if (! $tentativa->stderr_bruto && ! $tentativa->erro_simplificado) {
            return response()->json(['mensagem' => 'Esta tentativa não gerou mensagem de erro.'], 422);
        }

        if (! $tentativa->erro_simplificado) {
            $tentativa->update([
                'erro_simplificado' => $this->feedback->simplificarErro($tentativa->stderr_bruto),
            ]);
        }

        return response()->json([
            'texto' => $tentativa->erro_simplificado,
            'segmentos' => $this->segmentador->segmentar($tentativa->erro_simplificado),
        ]);
    }
}
