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

        <p>
            <label for="code">Kode</label><br>
            <input type="text" id="code" name="code" value="{{ old('code', $course->code) }}">
            @error('code')
                <br><span>{{ $message }}</span>
            @enderror
        </p>

        <p>
            <label for="name">Nama Mata Kuliah</label><br>
            <input type="text" id="name" name="name" value="{{ old('name', $course->name) }}">
            @error('name')
                <br><span>{{ $message }}</span>
            @enderror
        </p>

        <p>
            <label for="description">Deskripsi</label><br>
            <textarea id="description" name="description" rows="4">{{ old('description', $course->description) }}</textarea>
            @error('description')
                <br><span>{{ $message }}</span>
            @enderror
        </p>

        <p>
            <label for="sks">SKS</label><br>
            <input type="number" id="sks" name="sks" min="1" max="6" value="{{ old('sks', $course->sks) }}">
            @error('sks')
                <br><span>{{ $message }}</span>
            @enderror
        </p>

        <p>
            <label for="lecturer_id">Dosen</label><br>
            <select id="lecturer_id" name="lecturer_id">
                <option value="">Pilih Dosen</option>
                @foreach ($lecturers as $lecturer)
                    <option value="{{ $lecturer->id }}" @selected(old('lecturer_id', $course->lecturer_id) == $lecturer->id)>
                        {{ $lecturer->name }}
                    </option>
                @endforeach
            </select>
            @error('lecturer_id')
                <br><span>{{ $message }}</span>
            @enderror
        </p>

        <p>
            <label for="status">Status</label><br>
            <select id="status" name="status">
                <option value="">Pilih Status</option>
                <option value="draft" @selected(old('status', $course->status) === 'draft')>Draft</option>
                <option value="active" @selected(old('status', $course->status) === 'active')>Active</option>
                <option value="archived" @selected(old('status', $course->status) === 'archived')>Archived</option>
            </select>
            @error('status')
                <br><span>{{ $message }}</span>
            @enderror
        </p>

        <button type="submit">Simpan Perubahan</button>
        <a href="{{ route('courses.show', $course) }}">Batal</a>
    </form>

</x-layout>
