<?php

namespace App\Http\Controllers;

use App\Jobs\ProcessImportJob;
use App\Models\ImportHistory;
use App\Services\Import\DynamicImportService;
use App\Services\Import\HeaderDetector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ImportController extends Controller
{
    public function __construct(
        protected HeaderDetector      $detector,
        protected DynamicImportService $service,
    ) {}

    // -----------------------------------------------------------------------
    //  GET /import — landing page: list models & recent histories
    // -----------------------------------------------------------------------
    public function index()
    {
        $this->authorizeImport();

        // No more profiles, just histories
        $histories = ImportHistory::with('importer')
            ->orderByDesc('created_at')
            ->take(20)
            ->get();

        return view('import.upload', compact('histories'));
    }

    // -----------------------------------------------------------------------
    //  POST /import/upload — store file, detect headers, go to mapping
    // -----------------------------------------------------------------------
    public function upload(Request $request)
    {
        $this->authorizeImport();

        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls,csv,ods|max:20480',
        ]);

        // Store the file
        $storagePath = config('import-engine.storage_path', 'imports');
        $stored = $request->file('file')->store($storagePath);

        // Detect headers
        $filePath = Storage::path($stored);
        $headers  = $this->detector->detect($filePath);

        // Persist upload state to session
        session([
            'import.stored_path'        => $stored,
            'import.original_filename'  => $request->file('file')->getClientOriginalName(),
            'import.excel_headers'      => $headers,
        ]);

        return redirect()->route('import.mapping');
    }

    // -----------------------------------------------------------------------
    //  GET /import/mapping — show column mapping UI & model selector
    // -----------------------------------------------------------------------
    public function mapping()
    {
        $this->authorizeImport();
        $this->requireSession();

        $allowedModels = config('import-engine.allowed_models', []);
        $excelHeaders  = session('import.excel_headers');

        // Target model is whatever was selected previously, or null for first time
        $selectedModelFQCN = session('import.target_model');
        $dbColumns         = [];
        if ($selectedModelFQCN && class_exists($selectedModelFQCN)) {
            $dbColumns = (new $selectedModelFQCN)->getFillable();
        }

        $savedMap   = session('import.column_map', []);
        $importMode = session('import.import_mode', 'add');
        $uniqueKeys = session('import.unique_keys', []);

        return view('import.mapping', compact(
            'allowedModels', 'excelHeaders', 'dbColumns', 'savedMap', 'importMode', 'uniqueKeys', 'selectedModelFQCN'
        ));
    }

    // -----------------------------------------------------------------------
    //  POST /import/mapping — save mapping to session, go to preview
    // -----------------------------------------------------------------------
    public function saveMapping(Request $request)
    {
        $this->authorizeImport();
        $this->requireSession();

        // If the user just switched the Target Model dropdown without submitting the final map
        if ($request->has('switch_model_only')) {
            $request->validate(['target_model' => 'required|string']);
            session(['import.target_model' => $request->target_model]);
            // clear old map to avoid invalid columns
            session()->forget('import.column_map'); 
            return redirect()->route('import.mapping');
        }

        $request->validate([
            'target_model' => 'required|string',
            'column_map'   => 'required|array',
            'import_mode'  => 'required|in:add,replace,upsert',
            'unique_keys'  => 'nullable|array',
            // optional validation rules
            'rules'        => 'nullable|array',
        ]);

        session([
            'import.target_model' => $request->target_model,
            'import.column_map'   => $request->column_map,
            'import.import_mode'  => $request->import_mode,
            'import.unique_keys'  => $request->unique_keys ?? [],
            'import.rules'        => $request->rules ?? [],
        ]);

        return redirect()->route('import.preview');
    }

    // -----------------------------------------------------------------------
    //  GET /import/preview — first 10 rows with validation results
    // -----------------------------------------------------------------------
    public function preview()
    {
        $this->authorizeImport();
        $this->requireSession();

        if (!session('import.target_model')) {
            return redirect()->route('import.mapping')->withErrors('Silakan pilih target model terlebih dahulu.');
        }

        $filePath   = Storage::path(session('import.stored_path'));
        $columnMap  = session('import.column_map', []);
        $mode       = session('import.import_mode', 'add');
        $uniqueKeys = session('import.unique_keys', []);
        $rules      = session('import.rules', []); // Usually empty or populated via UI
        $targetName = class_basename(session('import.target_model'));

        $page        = max(1, (int) request('page', 1));
        $perPage     = 25;
        $previewRows = $this->service->preview($filePath, $columnMap, $rules, $perPage, session('import.target_model'), $uniqueKeys, $page);
        $totalRows   = $this->service->countRows($filePath);

        return view('import.preview', compact('previewRows', 'mode', 'uniqueKeys', 'columnMap', 'targetName', 'page', 'perPage', 'totalRows'));
    }

    // -----------------------------------------------------------------------
    //  POST /import/run — create ImportHistory and dispatch/run import
    // -----------------------------------------------------------------------
    public function run(Request $request)
    {
        $this->authorizeImport();
        $this->requireSession();

        $targetModel = session('import.target_model');
        $columnMap   = session('import.column_map', []);
        $mode        = session('import.import_mode', 'add');
        $uniqueKeys  = session('import.unique_keys', []);

        $history = ImportHistory::create([
            'target_model'      => $targetModel,
            'import_mode'       => $mode,
            'filename'          => session('import.stored_path'),
            'original_filename' => session('import.original_filename'),
            'status'            => 'pending',
            'imported_by'       => auth()->id(),
        ]);

        // Pass mapping info through the history object briefly for the service
        // Since we dropped profile_id, we can inject mapping config into the session
        // However, jobs might need these. We should pass them via the Job.
        // Actually, let's keep it simple: pass config via properties, or better yet,
        // we can store the map directly on the history model as JSON if we want.
        // For now, let's update dynamic import service to accept these parameters explicitly.
        
        // Wait, DynamicImportService expects config. Let's send the config into cache or session.
        // Better: let's save the mapping config directly in `import_histories` so it's traceable
        // Wait, we didn't add column_map to histories. Let's pass via cache or job payload.
        // Actually, $this->service->execute() could just accept the array.
        
        // Temporarily put in session? No, queue worker won't have session.
        // We need to pass mapping config to the job!
        $config = [
            'mode'        => $mode,
            'column_map'  => $columnMap,
            'unique_keys' => $uniqueKeys,
            'rules'       => session('import.rules', []),
        ];

        // Clear session BEFORE dispatching
        session()->forget(['import.target_model','import.stored_path','import.original_filename','import.excel_headers','import.column_map','import.import_mode','import.unique_keys','import.rules']);

        if (config('import-engine.enable_queue')) {
            ProcessImportJob::dispatch($history, $config);
            return redirect()->route('import.logs', $history)
                ->with('success', 'Import dijadwalkan. Proses berjalan di background.');
        }

        // Synchronous
        $this->service->execute($history, $config);

        $status = $history->fresh()->status === 'done' ? 'success' : 'error';
        $msg    = $history->fresh()->status === 'done'
            ? "Import selesai: {$history->fresh()->success_rows} baris berhasil, {$history->fresh()->failed_rows} baris gagal."
            : 'Import gagal. Lihat log untuk detail.';

        return redirect()->route('import.logs', $history)->with($status, $msg);
    }

    // -----------------------------------------------------------------------
    //  GET /import/logs/{history} — per-row error log
    // -----------------------------------------------------------------------
    public function logs(ImportHistory $history)
    {
        $this->authorizeImport();

        $history->load(['importer']);
        $logs = $history->logs()->orderBy('row_number')->paginate(50);

        return view('import.logs', compact('history', 'logs'));
    }

    // -----------------------------------------------------------------------
    //  GET /import/progress/{history} — JSON progress (for polling)
    // -----------------------------------------------------------------------
    public function progress(ImportHistory $history)
    {
        $this->authorizeImport();

        return response()->json([
            'status'       => $history->status,
            'total_rows'   => $history->total_rows,
            'success_rows' => $history->success_rows,
            'failed_rows'  => $history->failed_rows,
            'progress'     => $history->progressPercent(),
            'is_running'   => $history->isRunning(),
            'duration'     => $history->duration(),
        ]);
    }

    // -----------------------------------------------------------------------
    //  Helpers
    // -----------------------------------------------------------------------

    private function authorizeImport(): void
    {
        if (!auth()->user()->hasPermission('manage_import')) {
            abort(403, 'Anda tidak memiliki akses ke fitur Import.');
        }
    }

    private function requireSession(): void
    {
        if (!session()->has('import.stored_path')) {
            abort(redirect()->route('import.index'));
        }
    }
}
