<x-layout title="Halaman Tidak Ditemukan">

    <div class="flex min-h-[60vh] items-center justify-center">

        <div class="max-w-md text-center">

            <p class="text-7xl font-bold tracking-tight text-slate-900">
                404
            </p>

            <h1 class="mt-4 text-2xl font-bold text-slate-900">
                Halaman Tidak Ditemukan
            </h1>

            <p class="mt-2 text-sm leading-6 text-slate-500">
                Halaman yang Anda cari tidak tersedia atau mungkin
                sudah dipindahkan.
            </p>

            <div class="mt-6">
                <a
                    href="{{ route('dashboard') }}"
                    class="inline-flex rounded-lg bg-slate-900 px-5 py-2.5 text-sm font-semibold text-white transition hover:bg-slate-800"
                >
                    Kembali ke Dashboard
                </a>
            </div>

        </div>

    </div>

</x-layout>