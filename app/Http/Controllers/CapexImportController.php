<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessImportJob;
use App\Models\Capex;
use App\Models\ImportHistory;
use App\Services\Import\CapexImportService;
use App\Services\Import\HeaderDetector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

/**
 * Handles Excel import of CapEx items.
 *
 * Two contexts:
 * 1. Admin import all units: /capex/import            (needs manage_capex permission)
 * 2. Per-unit import:       /capex/{capex}/import     (needs manage_capex or manage_capex_unit)
 */
class CapexImportController extends Controller
{
    public function __construct(
        protected HeaderDetector   $detector,
        protected CapexImportService $service,
    ) {}

    // -----------------------------------------------------------------------
    //  Admin: Import for ALL units
    // -----------------------------------------------------------------------

    /**
     * GET /capex/import — Upload form for admin (all units)
     */
    public function uploadFormAll()
    {
        $this->checkPermission('manage_capex');
        return view('capex.import.upload', ['context' => 'all', 'capex' => null]);
    }

    /**
     * POST /capex/import/upload
     */
    public function uploadAll(Request $request)
    {
        $this->checkPermission('manage_capex');
        $request->validate([
            'file'        => 'required|file|mimes:xlsx,xls,csv,ods|max:20480',
            'fiscal_year' => 'required|integer|min:2020|max:2100',
        ]);

        $stored   = $request->file('file')->store('imports');
        $filePath = Storage::path($stored);
        $headers  = $this->detector->detect($filePath);

        session([
            'capex_import.stored_path'       => $stored,
            'capex_import.original_filename' => $request->file('file')->getClientOriginalName(),
            'capex_import.excel_headers'     => $headers,
            'capex_import.fiscal_year'       => (int) $request->fiscal_year,
            'capex_import.context'           => 'all',
            'capex_import.department_id'     => null,
        ]);

        return redirect()->route('capex.import.mapping');
    }

    /**
     * GET /capex/{capex}/import — Upload form per unit
     */
    public function uploadForm(Capex $capex)
    {
        $this->authorizeUnit($capex);
        $capex->load('department');
        return view('capex.import.upload', ['context' => 'unit', 'capex' => $capex]);
    }

    /**
     * POST /capex/{capex}/import/upload
     */
    public function upload(Request $request, Capex $capex)
    {
        $this->authorizeUnit($capex);
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv,ods|max:20480',
        ]);

        $stored   = $request->file('file')->store('imports');
        $filePath = Storage::path($stored);
        $headers  = $this->detector->detect($filePath);

        $capex->load('department');
        session([
            'capex_import.stored_path'       => $stored,
            'capex_import.original_filename' => $request->file('file')->getClientOriginalName(),
            'capex_import.excel_headers'     => $headers,
            'capex_import.fiscal_year'       => $capex->fiscal_year,
            'capex_import.context'           => 'unit',
            'capex_import.department_id'     => $capex->department_id,
            'capex_import.dept_code'         => strtoupper(trim($capex->department->code ?? '')),
            'capex_import.capex_id'          => $capex->id,
        ]);

        return redirect()->route('capex.import.mapping');
    }

    // -----------------------------------------------------------------------
    //  Shared: Mapping Step
    // -----------------------------------------------------------------------

    /**
     * GET /capex/import/mapping
     */
    public function mapping()
    {
        $this->requireCapexSession();

        $excelHeaders = session('capex_import.excel_headers', []);
        $savedMap     = session('capex_import.column_map', []);
        $mode         = session('capex_import.mode', 'upsert');
        $context      = session('capex_import.context');
        $capexId      = session('capex_import.capex_id');
        $capex        = $capexId ? Capex::with('department')->find($capexId) : null;

        // CapEx-specific system fields
        $systemFields = [
            'capex_id_number' => 'ID CapEx *',
            'item_name'       => 'Nama Item/Barang *',
            'capex_type'      => 'Kategori (New/Replacement)',
            'priority_scale'  => 'Skala Prioritas',
            'month'           => 'Bulan',
            'amount_per_year' => 'Amount/Tahun',
            'budget_amount'   => 'Nilai CapEx',
            'pic'             => 'PIC',
        ];

        if ($context === 'all') {
            $systemFields['unit_code'] = 'Kode Unit (wajib untuk import semua unit)';
        } else {
            // For per-unit: unit_code used to FILTER rows, not to resolve dept
            $deptCode = session('capex_import.dept_code', '');
            $systemFields['unit_code'] = "Kode Unit (filter — hanya baris kode '{$deptCode}' yang diimpor)";
        }

        return view('capex.import.mapping', compact(
            'excelHeaders', 'savedMap', 'mode', 'systemFields', 'context', 'capex'
        ));
    }

    /**
     * POST /capex/import/mapping
     */
    public function saveMapping(Request $request)
    {
        $this->requireCapexSession();

        $request->validate([
            'column_map' => 'required|array',
            'mode'       => 'required|in:add,replace,upsert',
        ]);

        session([
            'capex_import.column_map' => $request->column_map,
            'capex_import.mode'       => $request->mode,
        ]);

        return redirect()->route('capex.import.preview');
    }

    // -----------------------------------------------------------------------
    //  Shared: Preview Step
    // -----------------------------------------------------------------------

    /**
     * GET /capex/import/preview
     */
    public function preview(Request $request)
    {
        $this->requireCapexSession();

        $filePath  = Storage::path(session('capex_import.stored_path'));
        $columnMap = session('capex_import.column_map', []);
        $mode      = session('capex_import.mode', 'upsert');
        $context   = session('capex_import.context');
        $capexId   = session('capex_import.capex_id');
        $capex     = $capexId ? Capex::with('department')->find($capexId) : null;

        $page        = max(1, (int) $request->get('page', 1));
        $perPage     = 25;
        $deptCode    = session('capex_import.dept_code', '');
        $previewRows = $this->service->preview($filePath, $columnMap, $perPage, $page, $deptCode);
        $totalRows   = $this->service->countRows($filePath);

        return view('capex.import.preview', compact(
            'previewRows', 'mode', 'context', 'capex', 'page', 'perPage', 'totalRows'
        ));
    }

    // -----------------------------------------------------------------------
    //  Shared: Run Import
    // -----------------------------------------------------------------------

    /**
     * POST /capex/import/run
     */
    public function run(Request $request)
    {
        $this->requireCapexSession();

        $context      = session('capex_import.context');
        $deptId       = session('capex_import.department_id');
        $targetModel  = $context === 'all' ? 'CapEx (Semua Unit)' : 'CapEx (Per Unit)';

        $history = ImportHistory::create([
            'target_model'      => 'App\\Models\\CapexItem',
            'import_mode'       => session('capex_import.mode', 'upsert'),
            'filename'          => session('capex_import.stored_path'),
            'original_filename' => session('capex_import.original_filename'),
            'status'            => 'pending',
            'imported_by'       => auth()->id(),
        ]);

        $config = [
            'column_map'   => session('capex_import.column_map', []),
            'mode'         => session('capex_import.mode', 'upsert'),
            'fiscal_year'  => session('capex_import.fiscal_year', date('Y')),
            'department_id'=> $deptId,
            'context'      => $context,
            'dept_code'    => session('capex_import.dept_code', ''),
        ];

        // Clear session
        session()->forget([
            'capex_import.stored_path', 'capex_import.original_filename',
            'capex_import.excel_headers', 'capex_import.column_map',
            'capex_import.mode', 'capex_import.fiscal_year',
            'capex_import.context', 'capex_import.department_id', 'capex_import.capex_id',
        ]);

        // Run synchronously (CapexImportService doesn't extend ProcessImportJob)
        if ($context === 'all') {
            $this->service->executeAll($history, $config);
        } else {
            $this->service->executeForDept($history, $config, (int) $deptId);
        }

        $status = $history->fresh()->status === 'done' ? 'success' : 'error';
        $msg    = $history->fresh()->status === 'done'
            ? "Import selesai: {$history->fresh()->success_rows} baris berhasil."
            : 'Import gagal. Lihat log untuk detail.';

        return redirect()->route('import.logs', $history)->with($status, $msg);
    }

    // -----------------------------------------------------------------------
    //  Helpers
    // -----------------------------------------------------------------------

    private function requireCapexSession(): void
    {
        if (!session()->has('capex_import.stored_path')) {
            abort(redirect()->route('capex.index'));
        }
    }

    private function checkPermission(string $permission): void
    {
        if (!auth()->user()->hasPermission($permission)) {
            abort(403);
        }
    }

    private function authorizeUnit(Capex $capex): void
    {
        $user = auth()->user();
        if ($user->hasPermission('manage_capex')) return; // admin can always
        if (!$user->hasPermission('manage_capex_unit')) abort(403);

        // Unit users can only import to their own department's capex
        $primaryDept = $user->primaryDepartment()->first();
        $userDeptId  = $primaryDept?->id;
        if ($capex->department_id !== $userDeptId) abort(403);
    }
}
