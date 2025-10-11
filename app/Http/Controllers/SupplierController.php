<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SupplierController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('permission:manage_suppliers');
    }

    public function index(Request $request)
    {
        $query = Supplier::query();

        if ($search = trim((string) $request->query('search', ''))) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%$search%")
                  ->orWhere('code', 'like', "%$search%")
                  ->orWhere('email', 'like', "%$search%")
                  ->orWhere('phone', 'like', "%$search%");
            });
        }

        if ($request->filled('status')) {
            $status = $request->query('status');
            if ($status === 'active') $query->where('is_active', true);
            if ($status === 'inactive') $query->where('is_active', false);
        }

        $suppliers = $query->orderBy('name')->paginate(10)->withQueryString();

        return view('suppliers.index', compact('suppliers'));
    }

    public function create()
    {
        return view('suppliers.create');
    }

    public function store(Request $request)
    {
        try {
            $data = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'code' => ['required', 'string', 'max:50', 'unique:suppliers,code'],
                'email' => ['nullable', 'email', 'max:255'],
                'phone' => ['nullable', 'string', 'max:50'],
                'address' => ['nullable', 'string'],
                'is_active' => ['nullable', 'boolean'],
                'notes' => ['nullable', 'string'],
            ]);

            $data['is_active'] = (bool) ($data['is_active'] ?? true);

            $supplier = Supplier::create($data);

            return redirect()->route('suppliers.show', $supplier)->with('success', 'Supplier berhasil dibuat.');
        } catch (\Throwable $e) {
            \Log::error('Supplier store failed', [
                'error' => $e->getMessage(),
            ]);
            return back()->withInput()->with('error', 'Gagal menyimpan supplier.');
        }
    }

    public function show(Supplier $supplier)
    {
        return view('suppliers.show', compact('supplier'));
    }

    public function edit(Supplier $supplier)
    {
        return view('suppliers.edit', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier)
    {
        try {
            $data = $request->validate([
                'name' => ['required', 'string', 'max:255'],
                'code' => ['required', 'string', 'max:50', Rule::unique('suppliers', 'code')->ignore($supplier->id)],
                'email' => ['nullable', 'email', 'max:255'],
                'phone' => ['nullable', 'string', 'max:50'],
                'address' => ['nullable', 'string'],
                'is_active' => ['nullable', 'boolean'],
                'notes' => ['nullable', 'string'],
            ]);

            $data['is_active'] = (bool) ($data['is_active'] ?? $supplier->is_active);

            $supplier->update($data);

            return redirect()->route('suppliers.show', $supplier)->with('success', 'Supplier berhasil diperbarui.');
        } catch (\Throwable $e) {
            \Log::error('Supplier update failed', [
                'error' => $e->getMessage(),
                'supplier_id' => $supplier->id,
            ]);
            return back()->withInput()->with('error', 'Gagal memperbarui supplier.');
        }
    }

    public function destroy(Supplier $supplier)
    {
        $supplier->delete();
        return redirect()->route('suppliers.index')->with('success', 'Supplier dihapus.');
    }
}
