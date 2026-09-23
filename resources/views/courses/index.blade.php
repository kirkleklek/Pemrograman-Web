<x-layout title="Daftar Mata Kuliah">

    <h1>Daftar Mata Kuliah</h1>

    @if (session('success'))
        <p>{{ session('success') }}</p>
    @endif

    <p>
        <a href="{{ route('courses.create') }}">Tambah Mata Kuliah</a>
    </p>

    <form action="{{ route('courses.index') }}" method="GET">
        <label for="q">Cari Mata Kuliah</label>
        <input
            type="text"
            id="q"
            name="q"
            value="{{ request('q') }}"
            placeholder="Kode atau nama mata kuliah"
        >

        <label for="status">Status</label>
        <select id="status" name="status">
            <option value="">Semua Status</option>
            <option value="draft" @selected(request('status') === 'draft')>Draft</option>
            <option value="active" @selected(request('status') === 'active')>Active</option>
            <option value="archived" @selected(request('status') === 'archived')>Archived</option>
        </select>

        <button type="submit">Cari</button>
        <a href="{{ route('courses.index') }}">Reset</a>
    </form>

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

    {{ $courses->links() }}

</x-layout>
