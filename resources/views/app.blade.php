<!DOCTYPE html>
<html lang="de">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <link rel="icon" type="image/svg+xml" href="/favicon.svg">
        <title>{{ config('app.name', 'wechselstu.be') }}</title>
        <script>
            window.__APP_CONFIG__ = {!! json_encode([
                'ticketType' => request()->attributes->get('ticket_type'),
                'isMainDomain' => request()->getHost() === parse_url(config('app.url'), PHP_URL_HOST),
            ]) !!};
        </script>
        @vite(['resources/js/app.ts'])
    </head>
    <body class="font-sans antialiased">
        <div id="app"></div>
    </body>
</html>
