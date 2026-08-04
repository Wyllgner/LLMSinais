<?php

namespace App\Services;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Process\Exception\ProcessTimedOutException;
use Symfony\Component\Process\Process;

/**
 * Executa o codigo do aluno em container descartavel, sem rede e com limites
 * de memoria, CPU e processos.
 */
class SandboxService
{
    /**
     * Um laco infinito que imprime gera megabytes em segundos. Sem esse teto o
     * volume vai parar no banco e no prompt da LLM.
     */
    private const LIMITE_SAIDA = 8192;

    public function executar(string $codigo, string $entrada): array
    {
        $cfg = config('llmsinais.sandbox');
        $dir = rtrim($cfg['tmp'], '/').'/'.Str::uuid();

        File::ensureDirectoryExists($dir, 0755, true);
        File::put($dir.'/sol.py', $codigo);

        $nome = 'llms-'.Str::uuid();
        $proc = new Process($this->comando($cfg, $dir, $nome));
        $proc->setInput($entrada);
        $proc->setTimeout($cfg['timeout'] + 5);

        $stdout = '';
        $stderr = '';
        $truncado = false;

        try {
            $proc->run(function (string $tipo, string $trecho) use (&$stdout, &$stderr, &$truncado) {
                if ($tipo === Process::OUT) {
                    $stdout = $this->acumular($stdout, $trecho, $truncado);
                } else {
                    $stderr = $this->acumular($stderr, $trecho, $truncado);
                }
            });

            $saida = [
                'stdout' => $stdout,
                'stderr' => $stderr,
                'exit' => $proc->getExitCode(),
            ];
        } catch (ProcessTimedOutException) {
            // O timeout interno do container falhou, entao derruba pelo daemon.
            (new Process(['docker', 'kill', $nome]))->run();
            $saida = ['stdout' => $stdout, 'stderr' => $stderr, 'exit' => 137];
        } finally {
            File::deleteDirectory($dir);
        }

        $saida['status'] = $this->status($saida['exit']);
        $saida['truncado'] = $truncado;

        return $saida;
    }

    private function acumular(string $atual, string $trecho, bool &$truncado): string
    {
        $espaco = self::LIMITE_SAIDA - strlen($atual);

        if ($espaco <= 0) {
            $truncado = true;

            return $atual;
        }

        if (strlen($trecho) > $espaco) {
            $truncado = true;
        }

        return $atual.substr($trecho, 0, $espaco);
    }

    private function comando(array $cfg, string $dir, string $nome): array
    {
        return [
            'docker', 'run', '--rm', '-i',
            // Sem --init o Python vira PID 1, ignora SIGTERM e o laco infinito nunca morre.
            '--init',
            '--name', $nome,
            '--network', 'none',
            '--memory', $cfg['memory'],
            '--memory-swap', $cfg['memory'],
            '--cpus', '0.5',
            '--pids-limit', '64',
            '--read-only',
            '--tmpfs', '/tmp:size=8m',
            '-v', $dir.'/sol.py:/code/sol.py:ro',
            $cfg['image'],
            'timeout', '-s', 'KILL', (string) $cfg['timeout'],
            'python', '/code/sol.py',
        ];
    }

    private function status(?int $exit): string
    {
        // 137 vem do SIGKILL do timeout, 124 do timeout do coreutils.
        return match (true) {
            $exit === 0 => 'ok',
            in_array($exit, [124, 137], true) => 'timeout',
            default => 'erro',
        };
    }
}
