<!DOCTYPE html>
<html lang="de">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <meta name="csrf-token" content="{{ csrf_token() }}">
        <link rel="icon" type="image/svg+xml" href="/favicon.svg">
        <title>{{ config('app.name', 'wechselstu.be') }}</title>
        @vite(['resources/js/app.ts'])
    </head>
    <body class="font-sans antialiased">
        <div id="app"></div>
    </body>
</html>
