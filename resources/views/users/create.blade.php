<x-layout title="Tambah Pengguna">

    <h1>Tambah Pengguna</h1>

    @if ($errors->any())
        <div>
            <p>Data belum valid:</p>
            <ul>
                @foreach ($errors->all() as $error)
                    <li>{{ $error }}</li>
                @endforeach
            </ul>
        </div>
    @endif

    <form action="{{ route('users.store') }}" method="POST">
        @csrf

        <p>
            <label for="name">Nama</label><br>
            <input type="text" id="name" name="name" value="{{ old('name') }}">
            @error('name')
                <br><span>{{ $message }}</span>
            @enderror
        </p>

        <p>
            <label for="email">Email</label><br>
            <input type="email" id="email" name="email" value="{{ old('email') }}">
            @error('email')
                <br><span>{{ $message }}</span>
            @enderror
        </p>

        <p>
            <label for="nim_nip">NIM/NIP</label><br>
            <input type="text" id="nim_nip" name="nim_nip" value="{{ old('nim_nip') }}">
            @error('nim_nip')
                <br><span>{{ $message }}</span>
            @enderror
        </p>

        <p>
            <label for="role">Role</label><br>
            <select id="role" name="role">
                <option value="">Pilih Role</option>
                @foreach ($roles as $role)
                    <option value="{{ $role }}" @selected(old('role') === $role)>
                        {{ ucfirst($role) }}
                    </option>
                @endforeach
            </select>
            @error('role')
                <br><span>{{ $message }}</span>
            @enderror
        </p>

        <p>
            <label for="password">Password</label><br>
            <input type="password" id="password" name="password">
            @error('password')
                <br><span>{{ $message }}</span>
            @enderror
        </p>

        <p>
            <label for="password_confirmation">Konfirmasi Password</label><br>
            <input type="password" id="password_confirmation" name="password_confirmation">
        </p>

        <button type="submit">Simpan</button>
        <a href="{{ route('users.index') }}">Batal</a>
    </form>

</x-layout>
