<x-layout title="Tambah Mata Kuliah">

    <h1>Tambah Mata Kuliah</h1>

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

    <form action="{{ route('courses.store') }}" method="POST">
        @csrf

        <x-courses.form :lecturers="$lecturers" />

        <button type="submit">Simpan</button>

        <a href="{{ route('courses.index') }}">Batal</a>
    </form>

</x-layout>