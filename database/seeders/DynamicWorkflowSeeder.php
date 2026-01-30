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
            'hospital_director' => Role::where('name', 'hospital_director')->orWhere('name', 'direktur')->first(),
            'manager_pt' => Role::where('name', 'manager_pt')->first(),
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
                'nominal_max' => null,
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
                'name' => 'Peremajaan (≤ 50 Juta)',
                'description' => 'Workflow untuk peremajaan/renewal dengan nominal sampai dengan 50 juta rupiah',
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
                'nominal_max' => null,
                'nominal_range' => 'high',
                'priority' => 30,
                'is_active' => true,
                'is_specific_type' => true,
            ], $this->getPeremajaanHighSteps($roles));

        });

        $this->command->info('');
        $this->command->info('✅ Dynamic workflows seeded successfully!');
        $this->command->newLine();
        $this->command->info('📋 Flow Urutan:');
        $this->command->info('   1. APPROVAL: Maker → Approvers');
        $this->command->info('   2. PURCHASING: SPH 1, SPH 2 (existing system)');
        $this->command->info('   3. RELEASE: Releasers (after purchasing complete)');
        $this->command->newLine();
        
        $this->command->table(
            ['Workflow', 'Type', 'Nominal', 'Approval Steps', 'Release Steps'],
            ApprovalWorkflow::whereNotNull('procurement_type_id')
                ->with('procurementType')
                ->get()
                ->map(function ($w) {
                    $steps = $w->steps ?? [];
                    $approvalSteps = collect($steps)->filter(fn($s) => in_array($s->step_type ?? 'approver', ['maker', 'approver']))->count();
                    $releaseSteps = collect($steps)->filter(fn($s) => ($s->step_type ?? '') === 'releaser')->count();
                    return [
                        $w->name,
                        $w->procurementType->code ?? '-',
                        $w->nominal_range,
                        $approvalSteps,
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
            $this->requesterManagerStep(1, 'Manager Unit', 'Verifikasi & Input Harga', 'input_price'),
            $this->approverStep(2, 'Hospital Director', $roles['hospital_director'], null, 'approve'),
            $this->approverStep(3, 'Manager PT', $roles['manager_pt'], null, 'approve'),
            $this->approverStep(4, 'Manager Pembelian', $roles['purchasing'], null, 'approve'),
            
            // ═══ PHASE 2: PURCHASING (handled by existing PurchasingItem system) ═══
            // SPH 1, SPH 2 - tidak perlu step, akan auto-create PurchasingItem
            
            // ═══ PHASE 3: RELEASE (after purchasing complete) ═══
            $this->releaserStep(5, 'Manager Pembelian', $roles['purchasing']),
            $this->releaserStep(6, 'Manager PT', $roles['manager_pt']),
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
            $this->requesterManagerStep(1, 'Manager Unit', 'Verifikasi & Input Harga', 'input_price'),
            // FS wajib untuk nominal > 50jt (sesuai panduan)
            $this->financeFsStep(2, 'Manager Keuangan', $roles['manager_keuangan'], 'Pembuatan FS', 50000000),
            $this->approverStep(3, 'Hospital Director', $roles['hospital_director'], null, 'approve'),
            $this->approverStep(4, 'Manager PT', $roles['manager_pt'], null, 'approve'),
            $this->approverStep(5, 'Manager Pembelian', $roles['purchasing'], null, 'approve'),
            
            // ═══ PHASE 2: PURCHASING (handled by existing PurchasingItem system) ═══
            
            // ═══ PHASE 3: RELEASE (after purchasing complete) ═══
            $this->releaserStep(6, 'Manager Pembelian', $roles['purchasing']),
            $this->releaserStep(7, 'Manager PT', $roles['manager_pt']),
            $this->releaserStep(8, 'Direktur PT', $roles['direktur_pt']),
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
        return [
            // ═══ PHASE 1: APPROVAL ═══
            $this->requesterManagerStep(1, 'Manager Unit', 'Verifikasi & Input Harga', 'input_price'),
            $this->approverStep(2, 'Hospital Director', $roles['hospital_director'], null, 'approve'),
            $this->approverStep(3, 'Manager Pembelian', $roles['purchasing'], null, 'approve'),
            
            // ═══ PHASE 2: PURCHASING (handled by existing PurchasingItem system) ═══
            
            // ═══ PHASE 3: RELEASE (after purchasing complete) ═══
            $this->releaserStep(4, 'Manager Pembelian', $roles['purchasing']),
        ];
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
        return [
            // ═══ PHASE 1: APPROVAL ═══
            $this->requesterManagerStep(1, 'Manager Unit', 'Pemilihan ID Number CapEx', 'select_capex'),
            $this->approverStep(2, 'Hospital Director', $roles['hospital_director'], null, 'approve'),
            $this->approverStep(3, 'Manager PT', $roles['manager_pt'], null, 'approve'),
            $this->approverStep(4, 'Manager Pembelian', $roles['purchasing'], null, 'approve'),
            
            // ═══ PHASE 2: PURCHASING (handled by existing PurchasingItem system) ═══
            
            // ═══ PHASE 3: RELEASE (after purchasing complete) ═══
            $this->releaserStep(5, 'Manager Pembelian', $roles['purchasing']),
            $this->releaserStep(6, 'Manager PT', $roles['manager_pt']),
        ];
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
        return [
            // ═══ PHASE 1: APPROVAL ═══
            $this->requesterManagerStep(1, 'Manager Unit', 'Verifikasi & Input Harga', 'input_price'),
            // FS wajib untuk nominal > 50jt (sesuai panduan)
            $this->financeFsStep(2, 'Manager Keuangan', $roles['manager_keuangan'], 'Pembuatan FS', 50000000),
            $this->approverStep(3, 'Hospital Director', $roles['hospital_director'], null, 'approve'),
            $this->approverStep(4, 'Manager PT', $roles['manager_pt'], null, 'approve'),
            $this->approverStep(5, 'Manager Pembelian', $roles['purchasing'], null, 'approve'),
            
            // ═══ PHASE 2: PURCHASING (handled by existing PurchasingItem system) ═══
            
            // ═══ PHASE 3: RELEASE (after purchasing complete) ═══
            $this->releaserStep(6, 'Manager Pembelian', $roles['purchasing']),
            $this->releaserStep(7, 'Manager PT', $roles['manager_pt']),
            $this->releaserStep(8, 'Direktur PT', $roles['direktur_pt']),
        ];
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
            'is_conditional' => true,
            'condition_type' => 'total_price',
            'condition_value' => $thresholdValue,
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
    private function releaserStep(int $stepNumber, string $name, ?Role $role): object
    {
        return (object) [
            'step_number' => $stepNumber,
            'step_name' => $name . ' Release',
            'step_type' => 'releaser',
            'step_phase' => 'release', // Phase 3 (after purchasing)
            'approver_type' => 'role',
            'approver_id' => null,
            'approver_role_id' => $role?->id,
            'approver_department_id' => null,
            'scope_process' => 'Release',
            'required_action' => 'release',
            'can_insert_step' => false,
            'is_conditional' => false,
        ];
    }
}
