<x-layout title="Daftar Pengguna">

    <h1>Daftar Pengguna</h1>

    @if (session('success'))
        <p>{{ session('success') }}</p>
    @endif

    <p>
        <a href="{{ route('users.create') }}">Tambah Pengguna</a>
    </p>

    <table border="1">
        <tr>
            <th>Nama</th>
            <th>Email</th>
            <th>NIM/NIP</th>
            <th>Role</th>
            <th>Aksi</th>
        </tr>

        @forelse ($users as $user)
            <tr>
                <td>{{ $user->name }}</td>
                <td>{{ $user->email }}</td>
                <td>{{ $user->nim_nip ?? '-' }}</td>
                <td>{{ $user->role }}</td>
                <td>
                    <a href="{{ route('users.show', $user) }}">Detail</a>
                    <a href="{{ route('users.edit', $user) }}">Edit</a>

                    <form action="{{ route('users.destroy', $user) }}" method="POST" style="display: inline;" onsubmit="return confirm('Yakin ingin menghapus pengguna ini?')">
                        @csrf
                        @method('DELETE')
                        <button type="submit">Hapus</button>
                    </form>
                </td>
            </tr>
        @empty
            <tr>
                <td colspan="5">Belum ada data pengguna.</td>
            </tr>
        @endforelse
    </table>

</x-layout>
