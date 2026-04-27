<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Department;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    public function index(Request $request)
    {
        $query = User::with('role', 'departments');

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->whereRaw('LOWER(name) LIKE ?', ["%".strtolower($search)."%"])
                  ->orWhereRaw('LOWER(nik) LIKE ?', ["%".strtolower($search)."%"])
                  ->orWhereRaw('LOWER(username) LIKE ?', ["%".strtolower($search)."%"])
                  ->orWhereRaw('LOWER(email) LIKE ?', ["%".strtolower($search)."%"]);
            });
        }

        // Role filter
        if ($request->filled('role_id')) {
            $query->where('role_id', $request->role_id);
        }

        // Date range filter
        if ($request->filled('date_from')) {
            $query->where('created_at', '>=', $request->date_from);
        }

        if ($request->filled('date_to')) {
            $query->where('created_at', '<=', $request->date_to);
        }

        $users = $query->latest()->paginate(10)->withQueryString();
        $roles = Role::all();
        
        return view('users.index', compact('users', 'roles'));
    }

    public function create()
    {
        $roles = Role::all();
        $departments = Department::active()->orderBy('name')->get();
        return view('users.create', compact('roles', 'departments'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name'          => 'required|string|max:255',
            'nik'           => 'nullable|string|max:50|unique:users',
            'username'      => 'required|string|max:255|unique:users',
            'email'         => 'required|string|email|max:255|unique:users',
            'password'      => 'required|string|confirmed',
            'role_id'       => 'required|exists:roles,id',
            'department_id' => 'nullable|exists:departments,id',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $user = User::create([
            'name'     => $request->name,
            'nik'      => $request->nik,
            'username' => $request->username,
            'email'    => $request->email,
            'password' => Hash::make($request->password),
            'role_id'  => $request->role_id,
        ]);

        // Attach departemen ke pivot table jika dipilih
        if ($request->filled('department_id')) {
            $user->departments()->attach($request->department_id, [
                'is_primary'  => true,
                'is_manager'  => false,
                'start_date'  => now()->toDateString(),
            ]);
        }

        return redirect()->route('users.index')->with('success', 'User berhasil dibuat!');
    }

    public function edit(User $user)
    {
        $roles = Role::all();
        $departments = Department::active()->orderBy('name')->get();
        return view('users.edit', compact('user', 'roles', 'departments'));
    }

    public function update(Request $request, User $user)
    {
        $validator = Validator::make($request->all(), [
            'name'          => 'required|string|max:255',
            'nik'           => 'nullable|string|max:50|unique:users,nik,' . $user->id,
            'username'      => 'required|string|max:255|unique:users,username,' . $user->id,
            'email'         => 'required|string|email|max:255|unique:users,email,' . $user->id,
            'password'      => 'nullable|string|confirmed',
            'role_id'       => 'required|exists:roles,id',
            'department_id' => 'nullable|exists:departments,id',
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        $data = [
            'name'     => $request->name,
            'nik'      => $request->nik,
            'username' => $request->username,
            'email'    => $request->email,
            'role_id'  => $request->role_id,
        ];

        if ($request->filled('password')) {
            $data['password'] = Hash::make($request->password);
        }

        $user->update($data);

        // Sync departemen di pivot table
        if ($request->filled('department_id')) {
            // Ganti semua entri lama dengan departemen baru sebagai primary
            $user->departments()->sync([
                $request->department_id => [
                    'is_primary' => true,
                    'is_manager' => false,
                    'start_date' => now()->toDateString(),
                ],
            ]);
        } else {
            // Jika tidak dipilih, lepas semua departemen
            $user->departments()->detach();
        }

        return redirect()->route('users.index')->with('success', 'User berhasil diperbarui!');
    }

    public function destroy(User $user)
    {
        // Mencegah user menghapus dirinya sendiri
        if ($user->id === auth()->id()) {
            return redirect()->route('users.index')->with('error', 'Tidak dapat menghapus akun sendiri!');
        }

        $user->delete();
        return redirect()->route('users.index')->with('success', 'User berhasil dihapus!');
    }
}
