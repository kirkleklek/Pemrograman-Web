<!DOCTYPE html>
<html lang="id">
<head>
    <title>{{ $title ?? 'KampusLMS' }}</title>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>

<body>
    <nav>
        <a href="{{ route('dashboard') }}">Dashboard</a> |
        <a href="{{ route('courses.index') }}">Mata Kuliah</a> |
        <a href="{{ route('tentang') }}">Tentang</a>
    </nav>
    <hr>
    <main>
        {{ $slot }}
    </main>
</body>
</html>