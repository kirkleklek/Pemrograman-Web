<x-layout title="Daftar Mata Kuliah">

    <h1>Daftar Mata Kuliah</h1>

    @if (session('success'))
        <p>{{ session('success') }}</p>
    @endif

    <p>
        <a href="{{ route('courses.create') }}">Tambah Mata Kuliah</a>
    </p>

    <table border="1">
        <tr>
            <th>Kode</th>
            <th>Nama Mata Kuliah</th>
            <th>SKS</th>
            <th>Dosen</th>
            <th>Status</th>
            <th>Aksi</th>
        </tr>

        @forelse ($courses as $course)
            <tr>
                <td>{{ $course->code }}</td>
                <td>{{ $course->name }}</td>
                <td>{{ $course->sks }}</td>
                <td>{{ $course->lecturer?->name ?? '-' }}</td>
                <td>{{ $course->status }}</td>
                <td>
                    <a href="{{ route('courses.show', $course) }}">Detail</a>
                    <a href="{{ route('courses.edit', $course) }}">Edit</a>

                    <form action="{{ route('courses.destroy', $course) }}" method="POST" style="display: inline;" onsubmit="return confirm('Yakin ingin menghapus mata kuliah ini?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit">Hapus</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="6">Belum ada data mata kuliah.</td>
            </tr>
        @endforelse
    </table>

</x-layout>
