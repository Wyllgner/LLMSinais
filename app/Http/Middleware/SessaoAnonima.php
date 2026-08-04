<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

/**
 * Identifica o aluno sem exigir login: um UUID por sessao do navegador.
 * Autenticacao real esta declarada como trabalho futuro no artigo.
 */
class SessaoAnonima
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! $request->session()->has('sessao_uuid')) {
            $request->session()->put('sessao_uuid', (string) Str::uuid());
        }

        return $next($request);
    }
}
