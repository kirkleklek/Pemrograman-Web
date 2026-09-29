<x-layout title="Tentang">

    <div class="mx-auto max-w-4xl space-y-6">

        {{-- Header --}}
        <div>
            <p class="text-sm font-medium text-slate-500">
                Informasi Proyek
            </p>

            <h1 class="mt-1 text-3xl font-bold tracking-tight text-slate-900">
                Tentang KampusLMS
            </h1>

            <p class="mt-1 max-w-2xl text-sm leading-6 text-slate-500">
                Informasi singkat mengenai proyek dan anggota
                kelompok KampusLMS.
            </p>
        </div>


        {{-- Project Info --}}
        <div class="rounded-xl border border-slate-200 bg-white p-6 shadow-sm">

            <h2 class="text-lg font-semibold text-slate-900">
                KampusLMS Kelompok 03
            </h2>

            <p class="mt-2 text-sm leading-6 text-slate-600">
                KampusLMS merupakan sistem Learning Management System
                yang dikembangkan untuk mendukung kegiatan pembelajaran
                di lingkungan kampus.
            </p>

        </div>


        {{-- Anggota Kelompok --}}
        <div class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">

            <div class="border-b border-slate-200 px-6 py-4">
                <h2 class="text-lg font-semibold text-slate-900">
                    Anggota Kelompok
                </h2>
            </div>

            <div class="overflow-x-auto">

                <table class="min-w-full text-left text-sm">

                    <thead class="border-b border-slate-200 bg-slate-50">
                        <tr>
                            <th class="px-6 py-4 font-semibold text-slate-700">
                                No
                            </th>

                            <th class="px-6 py-4 font-semibold text-slate-700">
                                Nama
                            </th>

                            <th class="px-6 py-4 font-semibold text-slate-700">
                                NIM
                            </th>
                        </tr>
                    </thead>

                    <tbody class="divide-y divide-slate-100">

                        <tr class="hover:bg-slate-50">
                            <td class="px-6 py-4 text-slate-600">
                                1
                            </td>
                            <td class="px-6 py-4 font-medium text-slate-900">
                                Devin Raditya P
                            </td>
                            <td class="px-6 py-4 text-slate-600">
                                10241021
                            </td>
                        </tr>

                        <tr class="hover:bg-slate-50">
                            <td class="px-6 py-4 text-slate-600">
                                2
                            </td>
                            <td class="px-6 py-4 font-medium text-slate-900">
                                Dewi Bulan Purnama
                            </td>
                            <td class="px-6 py-4 text-slate-600">
                                10241023
                            </td>
                        </tr>

                        <tr class="hover:bg-slate-50">
                            <td class="px-6 py-4 text-slate-600">
                                3
                            </td>
                            <td class="px-6 py-4 font-medium text-slate-900">
                                Eagan Ferdian
                            </td>
                            <td class="px-6 py-4 text-slate-600">
                                10241025
                            </td>
                        </tr>

                        <tr class="hover:bg-slate-50">
                            <td class="px-6 py-4 text-slate-600">
                                4
                            </td>
                            <td class="px-6 py-4 font-medium text-slate-900">
                                Fabyo Nathanael Suoth
                            </td>
                            <td class="px-6 py-4 text-slate-600">
                                10241027
                            </td>
                        </tr>

                        <tr class="hover:bg-slate-50">
                            <td class="px-6 py-4 text-slate-600">
                                5
                            </td>
                            <td class="px-6 py-4 font-medium text-slate-900">
                                Fariz Daffa Abbiyu Rahmatullah
                            </td>
                            <td class="px-6 py-4 text-slate-600">
                                10241029
                            </td>
                        </tr>

                    </tbody>

                </table>

            </div>

        </div>

    </div>

</x-layout>