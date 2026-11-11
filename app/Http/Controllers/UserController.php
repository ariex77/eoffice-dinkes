<?php

namespace App\Http\Controllers;

use App\Models\Unit;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $users = User::with('unit')
            ->when(trim((string) $request->query('q')), function ($q, $s) {
                $q->where(fn ($w) => $w->where('name', 'like', "%{$s}%")->orWhere('email', 'like', "%{$s}%")->orWhere('nip', 'like', "%{$s}%"));
            })
            ->orderBy('name')->paginate(20)->withQueryString();

        return view('users.index', compact('users'));
    }

    public function create()
    {
        return view('users.form', ['user' => new User(['role' => 'staf', 'is_active' => true]), 'units' => Unit::orderBy('id')->get()]);
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules());
        $data['is_active'] = $request->boolean('is_active');
        User::create($data);

        return redirect()->route('users.index')->with('success', 'Pengguna ditambahkan.');
    }

    public function edit(User $user)
    {
        return view('users.form', ['user' => $user, 'units' => Unit::orderBy('id')->get()]);
    }

    public function update(Request $request, User $user)
    {
        $data = $request->validate($this->rules($user));
        $data['is_active'] = $request->boolean('is_active');

        if (blank($data['password'] ?? null)) {
            unset($data['password']);
        }

        // Cegah admin mengunci dirinya sendiri
        if ($user->id === $request->user()->id) {
            $data['role'] = 'admin';
            $data['is_active'] = true;
        }

        $user->update($data);

        return redirect()->route('users.index')->with('success', 'Data pengguna diperbarui.');
    }

    private function rules(?User $user = null): array
    {
        return [
            'name' => ['required', 'string', 'max:150'],
            'nip' => ['nullable', 'string', 'max:25', Rule::unique('users', 'nip')->ignore($user)],
            'jabatan' => ['nullable', 'string', 'max:200'],
            'kode_jabatan' => ['nullable', 'string', 'max:30'],
            'email' => ['required', 'email', 'max:150', Rule::unique('users', 'email')->ignore($user)],
            'role' => ['required', Rule::in(array_keys(User::ROLES))],
            'unit_id' => ['nullable', 'exists:units,id'],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:8'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }
}
