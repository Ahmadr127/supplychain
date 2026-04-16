<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\ApprovalWorkflow;
use App\Models\ProcurementType;
use App\Models\Role;
use Illuminate\Support\Facades\DB;

class DynamicWorkflowSeeder extends Seeder
{
    /**
     * Seed the 6 workflows based on:
     * - Procurement Type: BARANG_BARU or PEREMAJAAN
     * - Nominal Range: ≤10 Juta, 10-50 Juta, >50 Juta
     * 
     * FLOW URUTAN:
     * 1. APPROVAL: Maker → Approvers → [APPROVED]
     * 2. PURCHASING: SPH 1, SPH 2 (existing system - tidak diubah)
     * 3. RELEASE: Releasers → [FINAL RELEASE]
     * 
     * Catatan: Releasers baru aktif SETELAH purchasing selesai (vendor selected)
     */
    public function run(): void
    {
        // Get procurement types
        $barangBaru = ProcurementType::where('code', 'BARANG_BARU')->first();
        $peremajaan = ProcurementType::where('code', 'PEREMAJAAN')->first();
        
        // Ensure we have a default procurement type for the initial workflow if needed
        // For now we can use null or one of them, but the workflow itself is "initial"
        
        if (!$barangBaru || !$peremajaan) {
            $this->command->error('❌ Procurement types not found. Run ProcurementTypeSeeder first.');
            return;
        }

        // Get roles
        $roles = [
            'koordinator' => Role::where('name', 'koordinator')->first(),
            'manager_unit' => Role::where('name', 'manager_unit')->first(),
            'hospital_director' => Role::where('name', 'hospital_director')->first(),
            'general_manager_pt' => Role::where('name', 'general_manager_pt')->first(),
            'manager_fatp' => Role::where('name', 'manager_fatp')->first(),
            'purchasing' => Role::where('name', 'purchasing')->first(),
            'manager_keuangan' => Role::where('name', 'manager_keuangan')->first(),
            'direktur_pt' => Role::where('name', 'direktur_pt')->first(),
        ];

        // Log missing roles
        foreach ($roles as $name => $role) {
            if (!$role) {
                $this->command->warn("⚠️ Role '{$name}' not found. Some steps may not work correctly.");
            }
        }

        DB::transaction(function () use ($barangBaru, $peremajaan, $roles) {
            
            // ═══════════════════════════════════════════════════════════════════
            // WORKFLOW 0: DEFAULT INITIAL WORKFLOW (Maker -> Manager Unit)
            // ═══════════════════════════════════════════════════════════════════
            // This workflow is assigned on create. Manager Unit will input price.
            // System will then switch to the appropriate workflow based on price & type.
            $this->createWorkflow([
                'name' => 'Default Initial Workflow',
                'description' => 'Workflow awal default. Menunggu approval Manager Unit untuk penentuan harga dan workflow selanjutnya.',
                'type' => 'default_initial',
                'procurement_type_id' => null, // Not specific yet
                'nominal_min' => 0,
                'nominal_max' => null,
                'nominal_range' => 'low', // Use 'low' as placeholder (enum constraint)
                'priority' => 0, // Highest priority to be picked as default
                'is_active' => true,
                'is_specific_type' => false,
            ], $this->getDefaultInitialSteps($roles));

            // ═══════════════════════════════════════════════════════════════════
            // WORKFLOW 1: PENGADAAN BARANG BARU (Nominal ≤ 10 Juta)
            // ═══════════════════════════════════════════════════════════════════
            $this->createWorkflow([
                'name' => 'Pengadaan Barang Baru (≤ 10 Juta)',
                'description' => 'Workflow untuk pengadaan barang baru dengan nominal sampai dengan 10 juta rupiah',
                'type' => 'procurement_low',
                'procurement_type_id' => $barangBaru->id,
                'nominal_min' => 0,
                'nominal_max' => 10000000,
                'nominal_range' => 'low',
                'priority' => 10,
                'is_active' => true,
                'is_specific_type' => true,
            ], $this->getBarangBaruLowSteps($roles));

            // ═══════════════════════════════════════════════════════════════════
            // WORKFLOW 2: PENGADAAN BARANG BARU (Nominal 10 - 50 Juta)
            // ═══════════════════════════════════════════════════════════════════
            $this->createWorkflow([
                'name' => 'Pengadaan Barang Baru (10 - 50 Juta)',
                'description' => 'Workflow untuk pengadaan barang baru dengan nominal 10 sampai 50 juta rupiah',
                'type' => 'procurement_medium',
                'procurement_type_id' => $barangBaru->id,
                'nominal_min' => 10000000,
                'nominal_max' => 50000000,
                'nominal_range' => 'medium',
                'priority' => 20,
                'is_active' => true,
                'is_specific_type' => true,
            ], $this->getBarangBaruMediumSteps($roles));

            // ═══════════════════════════════════════════════════════════════════
            // WORKFLOW 3: PENGADAAN BARANG BARU (Nominal > 50 Juta)
            // ═══════════════════════════════════════════════════════════════════
            $this->createWorkflow([
                'name' => 'Pengadaan Barang Baru (> 50 Juta)',
                'description' => 'Workflow untuk pengadaan barang baru dengan nominal diatas 50 juta rupiah. Memerlukan FS dan approval Direktur PT.',
                'type' => 'procurement_high',
                'procurement_type_id' => $barangBaru->id,
                'nominal_min' => 50000000,
                'nominal_max' => 999999999999,
                'nominal_range' => 'high',
                'priority' => 30,
                'is_active' => true,
                'is_specific_type' => true,
            ], $this->getBarangBaruHighSteps($roles));

            // ═══════════════════════════════════════════════════════════════════
            // WORKFLOW 4: PEREMAJAAN (Nominal ≤ 10 Juta)
            // ═══════════════════════════════════════════════════════════════════
            $this->createWorkflow([
                'name' => 'Peremajaan (≤ 10 Juta)',
                'description' => 'Workflow untuk peremajaan/renewal dengan nominal sampai dengan 10 juta rupiah',
                'type' => 'renewal_low',
                'procurement_type_id' => $peremajaan->id,
                'nominal_min' => 0,
                'nominal_max' => 10000000,
                'nominal_range' => 'low',
                'priority' => 10,
                'is_active' => true,
                'is_specific_type' => true,
            ], $this->getPeremajaanLowSteps($roles));

            // ═══════════════════════════════════════════════════════════════════
            // WORKFLOW 5: PEREMAJAAN (Nominal ≤ 50 Juta)
            // ═══════════════════════════════════════════════════════════════════
            $this->createWorkflow([
                'name' => 'Peremajaan (10 - 50 Juta)',
                'description' => 'Workflow untuk peremajaan/renewal dengan nominal 10 sampai 50 juta rupiah',
                'type' => 'renewal_medium',
                'procurement_type_id' => $peremajaan->id,
                'nominal_min' => 10000000,
                'nominal_max' => 50000000,
                'nominal_range' => 'medium',
                'priority' => 20,
                'is_active' => true,
                'is_specific_type' => true,
            ], $this->getPeremajaanMediumSteps($roles));

            // ═══════════════════════════════════════════════════════════════════
            // WORKFLOW 6: PEREMAJAAN (Nominal > 50 Juta)
            // ═══════════════════════════════════════════════════════════════════
            $this->createWorkflow([
                'name' => 'Peremajaan (> 50 Juta)',
                'description' => 'Workflow untuk peremajaan/renewal dengan nominal diatas 50 juta rupiah. Memerlukan FS dan approval Direktur PT.',
                'type' => 'renewal_high',
                'procurement_type_id' => $peremajaan->id,
                'nominal_min' => 50000000,
                'nominal_max' => 999999999999,
                'nominal_range' => 'high',
                'priority' => 30,
                'is_active' => true,
                'is_specific_type' => true,
            ], $this->getPeremajaanHighSteps($roles));

        });
        
        $this->command->table(
            ['Workflow', 'Type', 'Nominal', 'Approval Steps', 'Purchasing Steps', 'Release Steps'],
            ApprovalWorkflow::whereNotNull('procurement_type_id')
                ->with('procurementType')
                ->get()
                ->map(function ($w) {
                    $steps = $w->steps ?? [];
                    $approvalSteps  = collect($steps)->filter(fn($s) => in_array($s->step_type ?? 'approver', ['maker', 'approver']))->count();
                    $purchasingSteps = collect($steps)->filter(fn($s) => ($s->step_type ?? '') === 'purchasing')->count();
                    $releaseSteps   = collect($steps)->filter(fn($s) => ($s->step_type ?? '') === 'releaser')->count();
                    return [
                        $w->name,
                        $w->procurementType->code ?? '-',
                        $w->nominal_range,
                        $approvalSteps,
                        $purchasingSteps > 0 ? "✅ {$purchasingSteps}" : '❌ 0',
                        $releaseSteps,
                    ];
                })->toArray()
        );
    }

    /**
     * Create or update workflow with steps
     */
    private function createWorkflow(array $data, array $steps): void
    {
        // Set both columns to support both old and new format
        $data['steps'] = $steps;
        $data['workflow_steps'] = $steps;
        
        $workflow = ApprovalWorkflow::updateOrCreate(
            [
                'procurement_type_id' => $data['procurement_type_id'],
                'nominal_range' => $data['nominal_range'],
            ],
            $data
        );

        $approvalCount = collect($steps)->filter(fn($s) => in_array($s->step_type, ['maker', 'approver']))->count();
        $releaseCount = collect($steps)->filter(fn($s) => $s->step_type === 'releaser')->count();

        $this->command->info("  📋 {$workflow->name}: {$approvalCount} approval + {$releaseCount} release steps");
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // BARANG BARU WORKFLOWS
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Steps for BARANG BARU ≤ 10 Juta
     * 
     * APPROVAL: Maker → 4 Approvers
     * PURCHASING: (existing system)
     * RELEASE: 2 Releasers
     */
    private function getBarangBaruLowSteps(array $roles): array
    {
        return [
            // ═══ PHASE 1: APPROVAL ═══
            $this->requesterManagerStep(1, 'Manager Unit', 'Pemilihan ID Number CapEx', 'select_capex'),
            $this->approverStep(2, 'Hospital Director', $roles['hospital_director'], 'Approve', 'approve'),
            $this->approverStep(3, 'General Manager PT', $roles['general_manager_pt'], 'Approve', 'approve'),
            $this->approverStep(4, 'Manager FATP', $roles['manager_fatp'], 'Approve', 'approve'),
            
            // ═══ PHASE 2: PURCHASING ═══
            ...$this->getPurchasingSteps(5, $roles),
            
            // ═══ PHASE 3: RELEASE (after purchasing complete) ═══
            $this->releaserStep(11, 'General Manager PT', $roles['general_manager_pt'], 'Final Approver'),
            $this->releaserStep(12, 'Manager FATP', $roles['manager_fatp'], 'Releaser'),
        ];
    }

    /**
     * Steps for BARANG BARU 10 - 50 Juta
     * Same structure as low (based on diagram)
     */
    private function getBarangBaruMediumSteps(array $roles): array
    {
        return $this->getBarangBaruLowSteps($roles);
    }

    /**
     * Steps for BARANG BARU > 50 Juta
     * 
     * APPROVAL: Maker → 5 Approvers (+ Manager Keuangan for FS)
     * PURCHASING: (existing system)
     * RELEASE: 3 Releasers (+ Direktur PT)
     */
    private function getBarangBaruHighSteps(array $roles): array
    {
        return [
            // ═══ PHASE 1: APPROVAL ═══
            $this->requesterManagerStep(1, 'Manager Unit', 'Pemilihan ID Number CapEx', 'select_capex'),
            // FS wajib untuk nominal > 50jt (sesuai panduan)
            $this->financeFsStep(2, 'Manager Keuangan', $roles['manager_keuangan'], 'Pembuatan FS', 50000000),
            $this->approverStep(3, 'Hospital Director', $roles['hospital_director'], 'Approve', 'approve'),
            $this->approverStep(4, 'General Manager PT', $roles['general_manager_pt'], 'Approve', 'approve'),
            $this->approverStep(5, 'Manager FATP', $roles['manager_fatp'], 'Approve', 'approve'),
            
            // ═══ PHASE 2: PURCHASING (handled by existing PurchasingItem system) ═══
            ...$this->getPurchasingSteps(6, $roles),
            
            // ═══ PHASE 3: RELEASE (after purchasing complete) ═══
            $this->releaserStep(12, 'General Manager PT', $roles['general_manager_pt'], 'Approver 6'),
            $this->releaserStep(13, 'Direktur PT', $roles['direktur_pt'], 'Final Approver'),
            $this->releaserStep(14, 'Manager FATP', $roles['manager_fatp'], 'Releaser'),
        ];
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // PEREMAJAAN WORKFLOWS
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Steps for PEREMAJAAN ≤ 10 Juta
     * 
     * APPROVAL: Maker → 3 Approvers
     * PURCHASING: (existing system)
     * RELEASE: 1 Releaser
     */
    private function getPeremajaanLowSteps(array $roles): array
    {
        return $this->getBarangBaruLowSteps($roles);
    }

    /**
     * Steps for PEREMAJAAN ≤ 50 Juta
     * 
     * APPROVAL: Maker → 4 Approvers
     * PURCHASING: (existing system)
     * RELEASE: 2 Releasers
     */
    private function getPeremajaanMediumSteps(array $roles): array
    {
        return $this->getBarangBaruMediumSteps($roles);
    }

    /**
     * Steps for PEREMAJAAN > 50 Juta
     * 
     * APPROVAL: Maker → 5 Approvers (+ Manager Keuangan for FS)
     * PURCHASING: (existing system)
     * RELEASE: 3 Releasers (+ Direktur PT)
     */
    private function getPeremajaanHighSteps(array $roles): array
    {
        return $this->getBarangBaruHighSteps($roles);
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // DEFAULT INITIAL WORKFLOW
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Steps for Default Initial Workflow
     * 
     * APPROVAL: Maker → Manager Unit (Input Price)
     * After Manager Unit approves, the system will switch to the real workflow.
     */
    private function getDefaultInitialSteps(array $roles): array
    {
        return [
            // ═══ PHASE 1: APPROVAL ═══
            // Manager Unit must input price to determine the next workflow
            $this->requesterManagerStep(1, 'Manager Unit', 'Verifikasi & Input Harga', 'input_price'),
        ];
    }

    // ═══════════════════════════════════════════════════════════════════════════
    // STEP HELPERS
    // ═══════════════════════════════════════════════════════════════════════════

    /**
     * Create Maker step definition
     * PIC: Koordinator / Kepala / Supervisor / Manager (requester's dept)
     * Phase: APPROVAL
     */
    private function makerStep(int $stepNumber): object
    {
        return (object) [
            'step_number' => $stepNumber,
            'step_name' => 'Maker',
            'step_type' => 'maker',
            'step_phase' => 'approval', // Phase 1
            'approver_type' => 'requester_department_manager',
            'approver_id' => null,
            'approver_role_id' => null,
            'approver_department_id' => null,
            'scope_process' => 'Input Administrasi Permintaan CapEx',
            'required_action' => 'create_document',
            'can_insert_step' => false,
            'is_conditional' => false,
        ];
    }

    /**
     * Create Approver step definition
     * Phase: APPROVAL
     */
    private function approverStep(
        int $stepNumber, 
        string $name, 
        ?Role $role, 
        ?string $scopeProcess, 
        string $requiredAction = 'approve'
    ): object {
        return (object) [
            'step_number' => $stepNumber,
            'step_name' => $name,
            'step_type' => 'approver',
            'step_phase' => 'approval', // Phase 1
            'approver_type' => 'role',
            'approver_id' => null,
            'approver_role_id' => $role?->id,
            'approver_department_id' => null,
            'scope_process' => $scopeProcess,
            'required_action' => $requiredAction,
            'can_insert_step' => false,
            'is_conditional' => false,
        ];
    }

 
    private function financeFsStep(
        int $stepNumber,
        string $name,
        ?Role $role,
        ?string $scopeProcess,
        int $thresholdValue
    ): object {
        return (object) [
            'step_number' => $stepNumber,
            'step_name' => $name,
            'step_type' => 'approver',
            'step_phase' => 'approval',
            'approver_type' => 'role',
            'approver_id' => null,
            'approver_role_id' => $role?->id,
            'approver_department_id' => null,
            'scope_process' => $scopeProcess,
            'required_action' => 'verify_budget',
            'can_insert_step' => false,
            'is_conditional' => false, // Dinonaktifkan karena workflow ini hanya untuk > 50 Juta
            'condition_type' => 'total_price',
            'condition_value' => $thresholdValue, // Diperlukan agar form UI memunculkan FS upload dengan threshold yang benar
        ];
    }

    /**
     * Create Requester Department Manager step definition
     * Phase: APPROVAL
     */
    private function requesterManagerStep(
        int $stepNumber, 
        string $name, 
        ?string $scopeProcess, 
        string $requiredAction = 'approve'
    ): object {
        return (object) [
            'step_number' => $stepNumber,
            'step_name' => $name,
            'step_type' => 'approver',
            'step_phase' => 'approval', // Phase 1
            'approver_type' => 'requester_department_manager',
            'approver_id' => null,
            'approver_role_id' => null,
            'approver_department_id' => null,
            'scope_process' => $scopeProcess,
            'required_action' => $requiredAction,
            'can_insert_step' => false,
            'is_conditional' => false,
        ];
    }

    /**
     * Create Releaser step definition
     * Phase: RELEASE (after purchasing complete)
     */
    private function releaserStep(int $stepNumber, string $name, ?Role $role, ?string $customName = null): object
    {
        return (object) [
            'step_number' => $stepNumber,
            'step_name' => $customName ?? ($name . ' Release'),
            'step_type' => 'releaser',
            'step_phase' => 'release', // Phase 3 (after purchasing)
            'approver_type' => 'role',
            'approver_id' => null,
            'approver_role_id' => $role?->id,
            'approver_department_id' => null,
            'scope_process' => $customName ?? 'Release',
            'required_action' => 'release',
            'can_insert_step' => false,
            'is_conditional' => false,
        ];
    }

    /**
     * Generate 6 sequential purchasing steps (Phase 2).
     * Starts at $startStep number.
     *
     * Step offset:  0 = Terima Dokumen (purchasing)
     *               1 = Benchmarking Vendor (purchasing)
     *               2 = Preferred Vendor (manager_keuangan via manage_vendor)
     *               3 = Input PO (purchasing)
     *               4 = Input Invoice & GRN (purchasing)
     *               5 = Mark Done (purchasing)
     */
    private function getPurchasingSteps(int $startStep, array $roles): array
    {
        $purchasing     = $roles['purchasing']  ?? null;
        $mgrKeuangan    = $roles['manager_keuangan'] ?? null;

        return [
            (object) [
                'step_number'    => $startStep,
                'step_name'      => 'Terima Dokumen',
                'step_type'      => 'purchasing',
                'step_phase'     => 'purchasing',
                'approver_type'  => 'role',
                'approver_id'    => null,
                'approver_role_id'         => $purchasing?->id,
                'approver_department_id'   => null,
                'scope_process'  => 'Set tanggal dokumen diterima',
                'required_action'=> 'purchasing_receive_doc',
                'can_insert_step'=> false,
                'is_conditional' => false,
            ],
            (object) [
                'step_number'    => $startStep + 1,
                'step_name'      => 'Benchmarking Vendor (SPH)',
                'step_type'      => 'purchasing',
                'step_phase'     => 'purchasing',
                'approver_type'  => 'role',
                'approver_id'    => null,
                'approver_role_id'         => $purchasing?->id,
                'approver_department_id'   => null,
                'scope_process'  => 'Input minimal 1 vendor (SPH)',
                'required_action'=> 'purchasing_benchmarking',
                'can_insert_step'=> false,
                'is_conditional' => false,
            ],
            (object) [
                'step_number'    => $startStep + 2,
                'step_name'      => 'Pilih Preferred Vendor',
                'step_type'      => 'purchasing',
                'step_phase'     => 'purchasing',
                'approver_type'  => 'role',
                'approver_id'    => null,
                'approver_role_id'         => $mgrKeuangan?->id,
                'approver_department_id'   => null,
                'scope_process'  => 'Manager Keuangan pilih vendor terbaik',
                'required_action'=> 'purchasing_preferred_vendor',
                'can_insert_step'=> false,
                'is_conditional' => false,
            ],
            (object) [
                'step_number'    => $startStep + 3,
                'step_name'      => 'Input PO',
                'step_type'      => 'purchasing',
                'step_phase'     => 'purchasing',
                'approver_type'  => 'role',
                'approver_id'    => null,
                'approver_role_id'         => $purchasing?->id,
                'approver_department_id'   => null,
                'scope_process'  => 'Input nomor Purchase Order',
                'required_action'=> 'purchasing_po',
                'can_insert_step'=> false,
                'is_conditional' => false,
            ],
            (object) [
                'step_number'    => $startStep + 4,
                'step_name'      => 'Input Invoice & GRN',
                'step_type'      => 'purchasing',
                'step_phase'     => 'purchasing',
                'approver_type'  => 'role',
                'approver_id'    => null,
                'approver_role_id'         => $purchasing?->id,
                'approver_department_id'   => null,
                'scope_process'  => 'Input nomor invoice dan tanggal GRN',
                'required_action'=> 'purchasing_invoice',
                'can_insert_step'=> false,
                'is_conditional' => false,
            ],
            (object) [
                'step_number'    => $startStep + 5,
                'step_name'      => 'Tandai Selesai (DONE)',
                'step_type'      => 'purchasing',
                'step_phase'     => 'purchasing',
                'approver_type'  => 'role',
                'approver_id'    => null,
                'approver_role_id'         => $purchasing?->id,
                'approver_department_id'   => null,
                'scope_process'  => 'Tutup proses purchasing',
                'required_action'=> 'purchasing_done',
                'can_insert_step'=> false,
                'is_conditional' => false,
            ],
        ];
    }
}
