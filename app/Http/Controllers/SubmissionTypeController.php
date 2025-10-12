<?php

namespace App\Http\Controllers;

use App\Models\SubmissionType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SubmissionTypeController extends Controller
{
    public function index(Request $request)
    {
        $query = SubmissionType::query();
        if ($request->filled('search')) {
            $s = $request->search;
            $query->where(function($q) use ($s){
                $q->where('name', 'like', "%{$s}%")
                  ->orWhere('code', 'like', "%{$s}%")
                  ->orWhere('description', 'like', "%{$s}%");
            });
        }
        $query->orderBy('name');
        $submissionTypes = $query->paginate(10)->withQueryString();
        return view('submission-types.index', compact('submissionTypes'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:20|unique:submission_types,code',
            'description' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
        ]);
        $data['is_active'] = (bool)($data['is_active'] ?? true);
        SubmissionType::create($data);
        return redirect()->back()->with('success', 'Submission type created.');
    }

    public function update(Request $request, SubmissionType $submissionType)
    {
        $data = $request->validate([
            'name' => 'required|string|max:100',
            'code' => 'required|string|max:20|unique:submission_types,code,' . $submissionType->id,
            'description' => 'nullable|string|max:255',
            'is_active' => 'nullable|boolean',
        ]);
        $data['is_active'] = (bool)($data['is_active'] ?? false);
        $submissionType->update($data);
        return redirect()->back()->with('success', 'Submission type updated.');
    }

    public function destroy(SubmissionType $submissionType)
    {
        $submissionType->delete();
        return redirect()->back()->with('success', 'Submission type deleted.');
    }
}
