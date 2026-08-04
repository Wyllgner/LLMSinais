<?php

return [

    'openai' => [
        'key'   => env('OPENAI_API_KEY'),
        'model' => env('OPENAI_MODEL', 'gpt-4o-mini'),
        'base'  => env('OPENAI_BASE', 'https://api.openai.com/v1'),
    ],

    'sandbox' => [
        'image'   => env('SANDBOX_IMAGE', 'python:3.11-alpine'),
        'timeout' => (int) env('SANDBOX_TIMEOUT', 5),
        'memory'  => env('SANDBOX_MEMORY', '128m'),
        'tmp'     => env('SANDBOX_TMP', '/tmp/llmsinais-sandbox'),
    ],

    'feedback' => [
        'nivel_maximo' => 4,
    ],

];
