<x-layout title="Edit Mata Kuliah">

    <h1>Edit Mata Kuliah</h1>

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

    <form action="{{ route('courses.update', $course) }}" method="POST">
        @csrf
        @method('PUT')

        <x-courses.form
            :course="$course"
            :lecturers="$lecturers"
        />

        <button type="submit">Simpan Perubahan</button>

        <a href="{{ route('courses.show', $course) }}">
            Batal
        </a>
    </form>

</x-layout>