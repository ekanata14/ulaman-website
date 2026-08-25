<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">

<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, viewport-fit=cover">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ isset($title) ? $title . ' - ' . config('app.name') : config('app.name') }}</title>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body class="min-h-screen font-sans antialiased bg-base-200/50">

    <livewire:timezone-detector />

    {{-- Shell minimal: seluruh chrome (menu/filter/kontrol) dirender oleh navbar
         floating milik komponen Spreadsheet agar rapi & tidak tumpang tindih. --}}
    <main class="min-h-screen">
        {{ $slot }}
    </main>

    <x-toast />

</body>

</html>
