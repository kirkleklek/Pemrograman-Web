<x-layout title="Detail Mata Kuliah">

    <h1>Detail Mata Kuliah</h1>

    @if (session('success'))
        <p>{{ session('success') }}</p>
    @endif

    <table border="1">
        <tr>
            <th>Kode</th>
            <td>{{ $course->code }}</td>
        </tr>

        <tr>
            <th>Nama Mata Kuliah</th>
            <td>{{ $course->name }}</td>
        </tr>

        <tr>
            <th>Deskripsi</th>
            <td>{{ $course->description ?? '-' }}</td>
        </tr>

        <tr>
            <th>SKS</th>
            <td>{{ $course->sks }}</td>
        </tr>

        <tr>
            <th>Dosen</th>
            <td>{{ $course->lecturer?->name ?? '-' }}</td>
        </tr>

        <tr>
            <th>Status</th>
            <td>{{ $course->status }}</td>
        </tr>
    </table>

    <br>

    <a href="{{ route('courses.edit', $course) }}">Edit Mata Kuliah</a>
    <br>

    <a href="{{ route('courses.index') }}">
        Kembali ke Daftar Mata Kuliah
    </a>

</x-layout>
