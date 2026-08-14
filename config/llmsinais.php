<?php

return [

    'openai' => [
        'key'   => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_MODEL', 'gpt-5-nano'),
        'base'  => env('OPENAI_BASE', 'https://api.openai.com/v1'),

        // A familia gpt-5 rejeita temperature diferente do padrao e usa
        // max_completion_tokens no lugar de max_tokens. O limite precisa de
        // folga porque os tokens de raciocinio contam aqui dentro.
        // O teto cobre raciocinio mais resposta. Com 800 e esforco low, o
        // modelo gastava a cota inteira pensando e devolvia conteudo vazio,
        // o que caia no feedback de reserva sem nenhum aviso.
        'max_tokens' => (int) env('OPENAI_MAX_TOKENS', 2500),

        // Medido nos dois sentidos, ver 6.8 do plano. Com minimal o custo cai
        // a quase nada, mas a dica sai truncada, inventa numero de linha e
        // entrega a correcao cedo demais. Com low o texto fica coerente, e os
        // verificadores seguram o que escapa. Qualidade ganha: o modelo e
        // barato, e uma dica ruim custa mais que tokens.
        'reasoning_effort' => env('OPENAI_REASONING_EFFORT', 'low'),

        // Precos em dolares por milhao de tokens, para o relatorio de custo.
        // Deixe vazio se nao souber: o relatorio mostra so os tokens.
        'preco_entrada' => env('OPENAI_PRECO_ENTRADA'),
        'preco_saida' => env('OPENAI_PRECO_SAIDA'),
    ],

    'sandbox' => [
        'image'   => env('SANDBOX_IMAGE', 'python:3.11-alpine'),
        'timeout' => (int) env('SANDBOX_TIMEOUT', 5),
        'memory'  => env('SANDBOX_MEMORY', '128m'),
        'tmp'     => env('SANDBOX_TMP', '/tmp/llmsinais-sandbox'),
    ],

    'feedback' => [
        'nivel_maximo' => 4,
        'erro_timeout' => 'O programa demorou demais. Ele não parou sozinho.',
    ],

];
