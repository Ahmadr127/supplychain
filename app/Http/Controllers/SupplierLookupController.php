<?php

namespace App\Http\Controllers;

use App\Models\Supplier;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class SupplierLookupController extends Controller
{
    public function suggest(Request $request)
    {
        $q = trim((string) $request->query('search', ''));
        $query = Supplier::query()->where('is_active', true);
        if ($q !== '') {
            $query->where(function($x) use ($q) {
                $x->where('name', 'like', "%$q%")
                  ->orWhere('code', 'like', "%$q%")
                  ->orWhere('email', 'like', "%$q%")
                  ->orWhere('phone', 'like', "%$q%");
            });
        }
        $suppliers = $query->orderBy('name')->limit(20)->get(['id','name','code','email','phone']);
        return response()->json(['success' => true, 'suppliers' => $suppliers]);
    }

    public function resolve(Request $request)
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['nullable','email','max:255'],
            'phone' => ['nullable','string','max:50'],
        ]);
        // Try find by exact name first
        $supplier = Supplier::where('name', $data['name'])->first();
        if (!$supplier) {
            // Generate code from slug and ensure uniqueness
            $base = strtoupper(Str::slug($data['name'], '_'));
            $code = substr($base, 0, 20) ?: 'SUP';
            $suffix = 1;
            while (Supplier::where('code', $code)->exists()) {
                $suffix++;
                $code = substr($base, 0, 20 - strlen((string)$suffix)) . $suffix;
            }
            $supplier = Supplier::create([
                'name' => $data['name'],
                'code' => $code,
                'email' => $data['email'] ?? null,
                'phone' => $data['phone'] ?? null,
                'is_active' => true,
            ]);
        }
        return response()->json(['success' => true, 'supplier' => $supplier]);
    }
}
