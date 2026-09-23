<x-layout title="Daftar Pengguna">

    <h1>Daftar Pengguna</h1>

    @if (session('success'))
        <p>{{ session('success') }}</p>
    @endif

    <p>
        <a href="{{ route('users.create') }}">Tambah Pengguna</a>
    </p>

<<<<<<< HEAD
    <form action="{{ route('users.index') }}" method="GET">
        <input type="text" name="q" value="{{ request('q') }}" placeholder="Cari nama atau email">

        <select name="role">
            <option value="">Semua role</option>
            @foreach ($roles as $role)
                <option value="{{ $role }}" @selected(request('role') === $role)>{{ $role }}</option>
            @endforeach
        </select>

        <button type="submit">Cari</button>
        <a href="{{ route('users.index') }}">Reset</a>
    </form>

=======
>>>>>>> 1ab157c195b3f37e9d83bf200db789f7c6fa3521
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

<<<<<<< HEAD
    {{ $users->links() }}

=======
>>>>>>> 1ab157c195b3f37e9d83bf200db789f7c6fa3521
</x-layout>
