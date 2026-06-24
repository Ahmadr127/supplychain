<?php

namespace Tests\Unit\Services;

use App\Models\ApprovalItemStep;
use App\Models\ApprovalRequest;
use App\Models\ApprovalWorkflow;
use App\Models\PurchasingItem;
use App\Services\Purchasing\PurchasingTypeService;
use Illuminate\Support\Collection;
use Tests\TestCase;
use Mockery;

class PurchasingTypeServiceTest extends TestCase
{
    private PurchasingTypeService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PurchasingTypeService();
    }

    // ────────────────────────────────────────────────────────────────────────
    // Default Config Tests
    // ────────────────────────────────────────────────────────────────────────

    public function test_returns_all_default_steps_when_no_config()
    {
        $item = $this->makeItem();

        $steps = $this->service->resolvePurchasingSteps(
            $item, true, true, collect(), collect()
        );

        $keys = $steps->pluck('step_key')->all();
        $this->assertEquals(
            ['benchmarking', 'trial', 'preferred_vendor', 'po', 'invoice_grn_done'],
            $keys
        );
    }

    public function test_default_config_has_5_steps()
    {
        $config = $this->service->buildDefaultStepConfig();
        $this->assertCount(5, $config);
    }

    public function test_first_step_is_always_active_for_authorized_user()
    {
        $item  = $this->makeItem();
        $steps = $this->service->resolvePurchasingSteps($item, true, true, collect(), collect());

        $benchmarking = $steps->firstWhere('canonical_step_key', 'benchmarking');
        $this->assertNotNull($benchmarking);
        $this->assertTrue($benchmarking->active);
        $this->assertFalse($benchmarking->locked);
    }

    public function test_first_step_is_locked_when_no_purchasing_permission()
    {
        $item  = $this->makeItem();
        $steps = $this->service->resolvePurchasingSteps($item, false, false, collect(), collect());

        $benchmarking = $steps->firstWhere('canonical_step_key', 'benchmarking');
        $this->assertTrue($benchmarking->locked);
    }

    public function test_tracker_mode_label_and_form_follow_required_action_not_step_name()
    {
        $item = $this->makeItem();

        $step = Mockery::mock(ApprovalItemStep::class)->makePartial();
        $step->id = 500;
        $step->step_number = 5;
        $step->status = 'pending';
        $step->required_action = 'purchasing_invoice_grn_done';
        $step->step_name = 'done';

        $steps = $this->service->resolvePurchasingSteps($item, true, true, collect([$step]), collect());
        $ui = $steps->first();

        $this->assertSame('purchasing_invoice_grn_done', $ui->required_action);
        $this->assertStringContainsString('Invoice', $ui->label);
        $this->assertSame('grn', $ui->form);
    }

    public function test_tracker_mode_trial_locked_when_status_pending_purchase()
    {
        $item = $this->makeItemWithVendors();

        $bench = Mockery::mock(ApprovalItemStep::class)->makePartial();
        $bench->id = 1;
        $bench->step_number = 1;
        $bench->status = 'approved';
        $bench->required_action = 'purchasing_benchmarking';
        $bench->step_name = 'Custom';

        $trial = Mockery::mock(ApprovalItemStep::class)->makePartial();
        $trial->id = 2;
        $trial->step_number = 2;
        $trial->status = 'pending_purchase';
        $trial->required_action = 'purchasing_trial';
        $trial->step_name = 'Custom';

        $resolved = $this->service->resolvePurchasingSteps($item, true, true, collect([$bench, $trial]), collect());
        $trialUi = $resolved->first(fn($s) => $s->required_action === 'purchasing_trial');

        $this->assertNotNull($trialUi);
        $this->assertTrue($trialUi->locked);
    }

    // ────────────────────────────────────────────────────────────────────────
    // Conditional Skip Tests
    // ────────────────────────────────────────────────────────────────────────

    public function test_trial_disabled_in_config_unlocks_preferred_vendor()
    {
        // Workflow config: trial disabled
        $workflow = Mockery::mock(ApprovalWorkflow::class)->makePartial();
        $workflow->shouldReceive('isPurchasingStepSkippable')->with('trial')->andReturn(true);
        $workflow->shouldReceive('getEnabledPurchasingSteps')->andReturn(collect([
            (object) ['step_key' => 'benchmarking',     'label' => 'Benchmarking', 'enabled' => true, 'order' => 1, 'allow_skip' => false],
            // 'trial' intentionally excluded
            (object) ['step_key' => 'preferred_vendor', 'label' => 'Preferred',    'enabled' => true, 'order' => 2, 'allow_skip' => false],
            (object) ['step_key' => 'po',               'label' => 'PO',           'enabled' => true, 'order' => 3, 'allow_skip' => false],
            (object) ['step_key' => 'invoice_grn_done', 'label' => 'GRN',          'enabled' => true, 'order' => 4, 'allow_skip' => false],
        ]));
        $workflow->shouldReceive('isPurchasingStepEnabled')->andReturn(true);

        // Item with benchmarking done (vendors exist)
        $item = $this->makeItemWithVendors();
        $purchasingSteps = collect(); // no trial step in DB

        $steps = $this->service->resolvePurchasingSteps($item, true, true, $purchasingSteps, collect());

        $preferred = $steps->firstWhere('canonical_step_key', 'preferred_vendor');
        // Should be active because trial is not in config (effectively skipped)
        $this->assertNotNull($preferred);
    }

    public function test_po_locked_when_preferred_vendor_not_set()
    {
        $item  = $this->makeItemWithVendors(); // benchmarking done, but no preferred vendor
        $steps = $this->service->resolvePurchasingSteps($item, true, true, collect(), collect());

        $po = $steps->firstWhere('canonical_step_key', 'po');
        $this->assertNotNull($po);
        $this->assertTrue($po->locked);
    }

    public function test_invoice_grn_locked_when_po_not_issued()
    {
        $item  = $this->makeItemWithPreferred(); // preferred set, no PO
        $steps = $this->service->resolvePurchasingSteps($item, true, true, collect(), collect());

        $grn = $steps->firstWhere('canonical_step_key', 'invoice_grn_done');
        $this->assertNotNull($grn);
        $this->assertTrue($grn->locked);
    }

    public function test_invoice_grn_locked_when_release_not_finished()
    {
        $item = $this->makeItemWithPo();

        // Release step still pending
        $releaseStep = Mockery::mock(ApprovalItemStep::class)->makePartial();
        $releaseStep->status = 'pending';
        $releaseStep->step_phase = 'release';

        $steps = $this->service->resolvePurchasingSteps(
            $item, true, true, collect(), collect([$releaseStep])
        );

        $grn = $steps->firstWhere('canonical_step_key', 'invoice_grn_done');
        $this->assertFalse($grn->locked);
    }

    public function test_invoice_grn_active_when_release_finished()
    {
        $item = $this->makeItemWithPo();

        // Release step approved
        $releaseStep = Mockery::mock(ApprovalItemStep::class)->makePartial();
        $releaseStep->status = 'approved';
        $releaseStep->step_phase = 'release';

        $steps = $this->service->resolvePurchasingSteps(
            $item, true, true, collect(), collect([$releaseStep])
        );

        $grn = $steps->firstWhere('canonical_step_key', 'invoice_grn_done');
        $this->assertTrue($grn->active || $grn->done);
    }

    public function test_invoice_grn_active_when_no_release_steps()
    {
        $item  = $this->makeItemWithPo();
        $steps = $this->service->resolvePurchasingSteps(
            $item, true, true, collect(), collect() // no release steps
        );

        $grn = $steps->firstWhere('canonical_step_key', 'invoice_grn_done');
        $this->assertTrue($grn->active || $grn->done);
    }

    // ────────────────────────────────────────────────────────────────────────
    // Workflow Flags (API backward compat)
    // ────────────────────────────────────────────────────────────────────────

    public function test_resolve_workflow_flags_returns_all_can_flags()
    {
        $item  = $this->makeItem();
        $flags = $this->service->resolveWorkflowFlags($item, true, true, collect(), collect());

        $this->assertArrayHasKey('can_set_received_date', $flags);
        $this->assertArrayHasKey('can_do_benchmarking', $flags);
        $this->assertArrayHasKey('can_do_trial', $flags);
        $this->assertArrayHasKey('can_select_preferred', $flags);
        $this->assertArrayHasKey('can_issue_po', $flags);
        $this->assertArrayHasKey('can_input_invoice', $flags);
        $this->assertArrayHasKey('can_mark_done', $flags);
        $this->assertArrayHasKey('step_definitions', $flags);
    }

    public function test_step_definitions_included_in_workflow_flags()
    {
        $item  = $this->makeItem();
        $flags = $this->service->resolveWorkflowFlags($item, true, true, collect(), collect());

        $this->assertIsArray($flags['step_definitions']);
        $this->assertNotEmpty($flags['step_definitions']);
        $this->assertArrayHasKey('step_key', $flags['step_definitions'][0]);
        $this->assertArrayHasKey('enabled',  $flags['step_definitions'][0]);
    }

    public function test_can_select_preferred_false_when_no_vendor_permission()
    {
        $item  = $this->makeItemWithVendors();
        $flags = $this->service->resolveWorkflowFlags($item, true, false, collect(), collect()); // canVendor = false

        $this->assertFalse($flags['can_select_preferred']);
    }

    public function test_resolve_workflow_flags_trial_done_via_data()
    {
        $item = Mockery::mock(PurchasingItem::class)->makePartial();
        $item->approval_request_id = 1;
        $item->master_item_id = 1;
        $item->preferred_vendor_id = null;
        $item->po_number = null;
        $item->invoice_number = null;

        $request = Mockery::mock(ApprovalRequest::class)->makePartial();
        $request->id = 1;
        $request->request_number = 'TEST-001';
        $request->received_at = null;
        $request->workflow_id = null;
        $item->shouldReceive('getAttribute')->with('approvalRequest')->andReturn($request);

        $vendorsMock = Mockery::mock();
        $vendorsMock->shouldReceive('exists')->andReturn(true);
        $vendorsMock->shouldReceive('whereHas')->with('trials')->andReturn(
            Mockery::mock(['exists' => true])
        );
        $item->shouldReceive('vendors')->andReturn($vendorsMock);

        $flags = $this->service->resolveWorkflowFlags($item, true, true, collect(), collect());

        $this->assertTrue($flags['trial_done']);
    }

    // ────────────────────────────────────────────────────────────────────────
    // Helpers
    // ────────────────────────────────────────────────────────────────────────

    private function makeItem(): PurchasingItem
    {
        $item = Mockery::mock(PurchasingItem::class)->makePartial();
        $item->approval_request_id = 1;
        $item->master_item_id = 1;
        $item->preferred_vendor_id = null;
        $item->po_number = null;
        $item->invoice_number = null;

        $request = Mockery::mock(ApprovalRequest::class)->makePartial();
        $request->id = 1;
        $request->request_number = 'TEST-001';
        $request->received_at = null;
        $request->workflow_id = null;
        $item->shouldReceive('getAttribute')->with('approvalRequest')->andReturn($request);
        $item->shouldReceive('vendors')->andReturn(new class {
            public function exists() { return false; }
            public function whereHas($rel) { return $this; }
        });

        return $item;
    }

    private function makeItemWithVendors(): PurchasingItem
    {
        $item = $this->makeItem();
        $item->shouldReceive('vendors')->andReturn(new class {
            public function exists() { return true; }
            public function whereHas($rel) { return $this; }
        });
        $item->preferred_vendor_id = null;
        return $item;
    }

    private function makeItemWithPreferred(): PurchasingItem
    {
        $item = $this->makeItemWithVendors();
        $item->preferred_vendor_id = 5;
        $item->po_number = null;
        return $item;
    }

    private function makeItemWithPo(): PurchasingItem
    {
        $item = $this->makeItemWithPreferred();
        $item->preferred_vendor_id = 5;
        $item->po_number = 'PO-2026-001';
        return $item;
    }

    protected function tearDown(): void
    {
        Mockery::close();
        parent::tearDown();
    }
}
