<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">

    <title>{{ $title ?? 'KampusLMS' }}</title>
</head>
<body>
    <nav>
        <a href="{{ route('dashboard') }}">Dashboard</a> |
        <a href="{{ route('courses.index') }}">Mata Kuliah</a> |
        <a href="{{ route('tentang') }}">Tentang</a>
    </nav>
    <hr>
<<<<<<< HEAD

    @if (session('success'))
        <div>
            {{ session('success') }}
        </div>
    @endif

    @if (session('error'))
        <div>
            {{ session('error') }}
        </div>
    @endif

=======
>>>>>>> 1ab157c195b3f37e9d83bf200db789f7c6fa3521
    <main>
        {{ $slot }}
    </main>

</body>
</html>