<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::query()
            ->when($request->filled('q'), function ($query) use ($request) {
                $q = $request->q;

                $query->where(function ($query) use ($q) {
                    $query->where('name', 'like', '%' . $q . '%')
                        ->orWhere('email', 'like', '%' . $q . '%');
                });
            })
            ->when($request->filled('role'), function ($query) use ($request) {
                $query->where('role', $request->role);
            })
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();

        return view('users.index', [
            'users' => $users,
            'roles' => $this->roles(),
        ]);
    }

    public function create()
    {
        return view('users.create', [
            'roles' => $this->roles(),
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules());

        $user = new User();
        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->nim_nip = $validated['nim_nip'] ?? null;
        $user->password = $validated['password'];
        $user->role = $validated['role'];
        $user->save();

        return redirect()
            ->route('admin.users.show', $user)
            ->with('success', 'Pengguna berhasil ditambahkan.');
    }

    public function show(User $user)
    {
        return view('users.show', compact('user'));
    }

    public function edit(User $user)
    {
        return view('users.edit', [
            'user' => $user,
            'roles' => $this->roles(),
        ]);
    }

    public function update(Request $request, User $user)
    {
        $validated = $request->validate($this->rules($user));

        $blockedReason = DB::transaction(function () use ($user, $validated): ?string {
            // Kunci daftar admin aktif terlebih dahulu agar dua request
            // bersamaan tidak dapat sama-sama menurunkan admin terakhir.
            $activeAdminIds = User::query()
                ->where('role', 'admin')
                ->orderBy('id')
                ->lockForUpdate()
                ->pluck('id');

            // Muat ulang target agar keputusan menggunakan data terbaru.
            $target = User::query()
                ->lockForUpdate()
                ->findOrFail($user->id);

            if (
                $target->role === 'admin'
                && $validated['role'] !== 'admin'
                && $activeAdminIds->count() <= 1
            ) {
                return 'last_admin';
            }

            if (
                $target->role === 'dosen'
                && $validated['role'] !== 'dosen'
                && $target->taughtCourses()->exists()
            ) {
                return 'lecturer_has_courses';
            }

            $target->name = $validated['name'];
            $target->email = $validated['email'];
            $target->nim_nip = $validated['nim_nip'] ?? null;
            $target->role = $validated['role'];

            if (! empty($validated['password'])) {
                $target->password = $validated['password'];
            }

            $target->save();

            return null;
        }, 3);

        if ($blockedReason === 'last_admin') {
            return redirect()
                ->route('admin.users.edit', $user)
                ->withErrors([
                    'role' => 'Role admin terakhir tidak dapat diubah. '
                        . 'Tambahkan admin lain sebelum mengubah role akun ini.',
                ]);
        }

        if ($blockedReason === 'lecturer_has_courses') {
            return redirect()
                ->route('admin.users.edit', $user)
                ->withErrors([
                    'role' => 'Role dosen tidak dapat diubah karena masih '
                        . 'mengampu mata kuliah. Alihkan mata kuliah ke '
                        . 'dosen lain terlebih dahulu.',
                ]);
        }

        return redirect()
            ->route('admin.users.show', $user)
            ->with('success', 'Pengguna berhasil diperbarui.');
    }

    public function destroy(User $user)
    {
        $blockedReason = DB::transaction(function () use ($user): ?string {
            // Kunci seluruh admin aktif sebelum menghitung admin yang tersisa.
            $activeAdminIds = User::query()
                ->where('role', 'admin')
                ->orderBy('id')
                ->lockForUpdate()
                ->pluck('id');

            $target = User::query()
                ->lockForUpdate()
                ->findOrFail($user->id);

            if (
                $target->role === 'admin'
                && $activeAdminIds->count() <= 1
            ) {
                return 'last_admin';
            }

            if (
                $target->role === 'dosen'
                && $target->taughtCourses()->exists()
            ) {
                return 'lecturer_has_courses';
            }

            $target->delete();

            return null;
        }, 3);

        if ($blockedReason === 'last_admin') {
            return redirect()
                ->route('admin.users.index')
                ->with(
                    'error',
                    'Admin terakhir tidak dapat dihapus. '
                    . 'Tambahkan admin lain sebelum menghapus akun ini.'
                );
        }

        if ($blockedReason === 'lecturer_has_courses') {
            return redirect()
                ->route('admin.users.index')
                ->with(
                    'error',
                    'Dosen tidak dapat dihapus karena masih mengampu mata kuliah. '
                    . 'Alihkan mata kuliah ke dosen lain terlebih dahulu.'
                );
        }

        return redirect()
            ->route('admin.users.index')
            ->with('success', 'Pengguna berhasil dihapus.');
    }

    private function rules(?User $user = null): array
    {
        $passwordRules = $user
            ? ['nullable', 'string', 'min:8', 'confirmed']
            : ['required', 'string', 'min:8', 'confirmed'];

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'required',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($user),
            ],
            'nim_nip' => [
                'nullable',
                'string',
                'max:255',
                Rule::unique('users', 'nim_nip')->ignore($user),
            ],
            'role' => ['required', Rule::in($this->roles())],
            'password' => $passwordRules,
        ];
    }

    private function roles(): array
    {
        return ['admin', 'dosen', 'mahasiswa'];
    }
}