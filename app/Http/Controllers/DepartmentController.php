<?php

namespace App\Http\Controllers;

use App\Models\Department;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class DepartmentController extends Controller
{
    public function index(Request $request)
    {
        $query = Department::with(['parent', 'manager', 'users']);

        // Search filter
        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('code', 'like', "%{$search}%")
                  ->orWhere('description', 'like', "%{$search}%");
            });
        }

        // Level filter
        if ($request->filled('level')) {
            $query->where('level', $request->level);
        }

        // Status filter
        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $departments = $query->latest()->paginate(10)->withQueryString();
        
        return view('departments.index', compact('departments'));
    }

    public function create()
    {
        $departments = Department::where('is_active', true)->get();
        $users = User::with('role')->get();
        
        return view('departments.create', compact('departments', 'users'));
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10|unique:departments',
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:departments,id',
            'manager_id' => 'nullable|exists:users,id',
            'level' => 'required|integer|min:1|max:5',
            'approval_level' => 'required|integer|min:1|max:5',
            'is_active' => 'boolean',
            'members' => 'array',
            'members.*.user_id' => 'required|exists:users,id',
            'members.*.position' => 'required|string|max:255',
            'members.*.is_primary' => 'boolean'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Check if manager is already a manager of another department
        if ($request->manager_id) {
            $existingManager = Department::where('manager_id', $request->manager_id)->first();
            if ($existingManager) {
                return redirect()->back()->withErrors([
                    'manager_id' => 'User ini sudah menjadi manager di departemen ' . $existingManager->name
                ])->withInput();
            }
        }

        $department = Department::create([
            'name' => $request->name,
            'code' => strtoupper($request->code),
            'description' => $request->description,
            'parent_id' => $request->parent_id,
            'manager_id' => $request->manager_id,
            'level' => $request->level,
            'approval_level' => $request->approval_level,
            'is_active' => $request->has('is_active')
        ]);

        // Add members to department
        if ($request->members) {
            foreach ($request->members as $member) {
                // Check if user is already a manager of another department
                $existingManager = Department::where('manager_id', $member['user_id'])->first();
                if ($existingManager) {
                    return redirect()->back()->withErrors([
                        'members' => 'User ' . User::find($member['user_id'])->name . ' sudah menjadi manager di departemen lain'
                    ])->withInput();
                }

                $department->users()->attach($member['user_id'], [
                    'position' => $member['position'],
                    'is_primary' => isset($member['is_primary']),
                    'is_manager' => false,
                    'start_date' => now(),
                ]);
            }
        }

        // Add manager as member if not already added
        if ($request->manager_id) {
            $isManagerAlreadyMember = $department->users()->wherePivot('user_id', $request->manager_id)->exists();
            if (!$isManagerAlreadyMember) {
                $department->users()->attach($request->manager_id, [
                    'position' => 'Manager',
                    'is_primary' => true,
                    'is_manager' => true,
                    'start_date' => now(),
                ]);
            }
        }

        return redirect()->route('departments.index')->with('success', 'Department berhasil dibuat!');
    }

    public function show(Department $department)
    {
        $department->load(['parent', 'children', 'manager', 'users', 'managers']);
        
        return view('departments.show', compact('department'));
    }

    public function edit(Department $department)
    {
        $departments = Department::where('is_active', true)
                                ->where('id', '!=', $department->id)
                                ->get();
        $users = User::with('role')->get();
        
        return view('departments.edit', compact('department', 'departments', 'users'));
    }

    public function update(Request $request, Department $department)
    {
        $validator = Validator::make($request->all(), [
            'name' => 'required|string|max:255',
            'code' => 'required|string|max:10|unique:departments,code,' . $department->id,
            'description' => 'nullable|string',
            'parent_id' => 'nullable|exists:departments,id',
            'manager_id' => 'nullable|exists:users,id',
            'level' => 'required|integer|min:1|max:5',
            'approval_level' => 'required|integer|min:1|max:5',
            'is_active' => 'boolean',
            'members' => 'array',
            'members.*.user_id' => 'required|exists:users,id',
            'members.*.position' => 'required|string|max:255',
            'members.*.is_primary' => 'boolean'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Check if manager is already a manager of another department (excluding current department)
        if ($request->manager_id && $request->manager_id != $department->manager_id) {
            $existingManager = Department::where('manager_id', $request->manager_id)
                                        ->where('id', '!=', $department->id)
                                        ->first();
            if ($existingManager) {
                return redirect()->back()->withErrors([
                    'manager_id' => 'User ini sudah menjadi manager di departemen ' . $existingManager->name
                ])->withInput();
            }
        }

        $department->update([
            'name' => $request->name,
            'code' => strtoupper($request->code),
            'description' => $request->description,
            'parent_id' => $request->parent_id,
            'manager_id' => $request->manager_id,
            'level' => $request->level,
            'approval_level' => $request->approval_level,
            'is_active' => $request->has('is_active')
        ]);

        // Update members - remove all existing members first
        $department->users()->detach();

        // Add new members
        if ($request->members) {
            foreach ($request->members as $member) {
                // Check if user is already a manager of another department
                $existingManager = Department::where('manager_id', $member['user_id'])
                                            ->where('id', '!=', $department->id)
                                            ->first();
                if ($existingManager) {
                    return redirect()->back()->withErrors([
                        'members' => 'User ' . User::find($member['user_id'])->name . ' sudah menjadi manager di departemen lain'
                    ])->withInput();
                }

                $department->users()->attach($member['user_id'], [
                    'position' => $member['position'],
                    'is_primary' => isset($member['is_primary']),
                    'is_manager' => false,
                    'start_date' => now(),
                ]);
            }
        }

        // Add manager as member if not already added
        if ($request->manager_id) {
            $isManagerAlreadyMember = $department->users()->wherePivot('user_id', $request->manager_id)->exists();
            if (!$isManagerAlreadyMember) {
                $department->users()->attach($request->manager_id, [
                    'position' => 'Manager',
                    'is_primary' => true,
                    'is_manager' => true,
                    'start_date' => now(),
                ]);
            }
        }

        return redirect()->route('departments.index')->with('success', 'Department berhasil diperbarui!');
    }

    public function destroy(Department $department)
    {
        // Check if department has children
        if ($department->children()->count() > 0) {
            return redirect()->back()->with('error', 'Tidak dapat menghapus department yang memiliki sub-department!');
        }

        // Check if department has users
        if ($department->users()->count() > 0) {
            return redirect()->back()->with('error', 'Tidak dapat menghapus department yang memiliki user!');
        }

        $department->delete();
        return redirect()->route('departments.index')->with('success', 'Department berhasil dihapus!');
    }

    public function assignUser(Request $request, Department $department)
    {
        $validator = Validator::make($request->all(), [
            'user_id' => 'required|exists:users,id',
            'position' => 'required|string|max:255',
            'is_primary' => 'boolean',
            'is_manager' => 'boolean',
            'start_date' => 'required|date'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Check if user already assigned to this department
        if ($department->users()->wherePivot('user_id', $request->user_id)->exists()) {
            return redirect()->back()->with('error', 'User sudah terdaftar di department ini!');
        }

        $department->users()->attach($request->user_id, [
            'position' => $request->position,
            'is_primary' => $request->has('is_primary'),
            'is_manager' => $request->has('is_manager'),
            'start_date' => $request->start_date,
        ]);

        // If user is set as manager, update department manager
        if ($request->has('is_manager')) {
            $department->update(['manager_id' => $request->user_id]);
        }

        return redirect()->back()->with('success', 'User berhasil ditambahkan ke department!');
    }

    public function removeUser(Department $department, User $user)
    {
        $department->users()->detach($user->id);
        
        // If user was manager, remove manager from department
        if ($department->manager_id === $user->id) {
            $department->update(['manager_id' => null]);
        }

        return redirect()->back()->with('success', 'User berhasil dihapus dari department!');
    }
}
