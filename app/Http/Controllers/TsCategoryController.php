<?php

namespace App\Http\Controllers;

use App\Models\TsCategory;
use App\Models\User;
use Illuminate\Http\Request;

class TsCategoryController extends Controller
{
    public function __construct()
    {
        // Require permission (using existing pattern, maybe manage_settings or a custom one)
        $this->middleware('permission:manage_settings');
    }

    public function index()
    {
        $categories = TsCategory::with(['approverUser', 'approverRole'])->orderBy('name')->get();
        return view('ts-categories.index', compact('categories'));
    }

    public function create()
    {
        $users = User::with('role')->orderBy('name')->get();
        $roles = \App\Models\Role::orderBy('name')->get();
        return view('ts-categories.create', compact('users', 'roles'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'ts_approver_type' => 'required|string|in:user,role,department_manager',
            'ts_approver_id' => 'nullable|required_if:ts_approver_type,user|exists:users,id',
            'ts_approver_role_id' => 'nullable|required_if:ts_approver_type,role|exists:roles,id',
        ]);

        TsCategory::create($validated);

        return redirect()->route('ts-categories.index')
            ->with('success', 'Kategori TS berhasil ditambahkan.');
    }

    public function edit(TsCategory $tsCategory)
    {
        $users = User::with('role')->orderBy('name')->get();
        $roles = \App\Models\Role::orderBy('name')->get();
        return view('ts-categories.edit', compact('tsCategory', 'users', 'roles'));
    }

    public function update(Request $request, TsCategory $tsCategory)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'description' => 'nullable|string',
            'is_active' => 'boolean',
            'ts_approver_type' => 'required|string|in:user,role,department_manager',
            'ts_approver_id' => 'nullable|required_if:ts_approver_type,user|exists:users,id',
            'ts_approver_role_id' => 'nullable|required_if:ts_approver_type,role|exists:roles,id',
        ]);

        // Default to false if not present
        $validated['is_active'] = $request->has('is_active');

        // Reset irrelevant approver fields based on type
        if ($validated['ts_approver_type'] !== 'user') {
            $validated['ts_approver_id'] = null;
        }
        if ($validated['ts_approver_type'] !== 'role') {
            $validated['ts_approver_role_id'] = null;
        }

        $tsCategory->update($validated);

        return redirect()->route('ts-categories.index')
            ->with('success', 'Kategori TS berhasil diupdate.');
    }

    public function destroy(TsCategory $tsCategory)
    {
        $tsCategory->delete();
        return redirect()->route('ts-categories.index')
            ->with('success', 'Kategori TS berhasil dihapus.');
    }
}
