<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
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
            ->route('users.show', $user)
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

        $user->name = $validated['name'];
        $user->email = $validated['email'];
        $user->nim_nip = $validated['nim_nip'] ?? null;
        $user->role = $validated['role'];

        if (! empty($validated['password'])) {
            $user->password = $validated['password'];
        }

        $user->save();

        return redirect()
            ->route('users.show', $user)
            ->with('success', 'Pengguna berhasil diperbarui.');
    }

    public function destroy(User $user)
    {
        $user->delete();

        return redirect()
            ->route('users.index')
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
