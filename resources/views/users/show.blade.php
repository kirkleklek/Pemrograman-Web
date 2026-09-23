<x-layout title="Detail Pengguna">

    <h1>Detail Pengguna</h1>

    @if (session('success'))
        <p>{{ session('success') }}</p>
    @endif

    <table border="1">
        <tr>
            <th>Nama</th>
            <td>{{ $user->name }}</td>
        </tr>

        <tr>
            <th>Email</th>
            <td>{{ $user->email }}</td>
        </tr>

        <tr>
            <th>NIM/NIP</th>
            <td>{{ $user->nim_nip ?? '-' }}</td>
        </tr>

        <tr>
            <th>Role</th>
            <td>{{ $user->role }}</td>
        </tr>
    </table>

    <br>

    <a href="{{ route('users.edit', $user) }}">Edit Pengguna</a>
    <br>

    <a href="{{ route('users.index') }}">
        Kembali ke Daftar Pengguna
    </a>

</x-layout>
