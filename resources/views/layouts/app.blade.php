<!DOCTYPE html>
<html lang="pt-BR">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('titulo', 'LLMSinais')</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="min-h-screen antialiased">

    <header class="px-4 pt-4">
        <div class="clay mx-auto flex max-w-[1600px] items-center justify-between px-6 py-3">
            <a href="{{ route('trilha') }}" class="flex items-center gap-3">
                <span class="clay-selo flex h-10 w-10 items-center justify-center bg-lilas text-lg font-bold text-white">
                    L
                </span>
                <span class="text-lg font-bold tracking-tight text-argila-tinta">LLMSinais</span>
            </a>
            <span class="text-sm font-medium text-argila-tinta-fraca">Programação I acessível</span>
        </div>
    </header>

    <main>
        @yield('conteudo')
    </main>

    @stack('scripts')

</body>
</html>
