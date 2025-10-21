<?php

namespace App\Http\Controllers;

use App\Models\Setting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SettingController extends Controller
{
    /**
     * Display settings page
     */
    public function index()
    {
        // Get approval request settings
        $settings = Setting::getGroup('approval_request');
        
        return view('settings.index', compact('settings'));
    }

    /**
     * Update settings
     */
    public function update(Request $request)
    {
        // Sanitize formatted rupiah inputs BEFORE validation
        $data = $request->all();
        $data['fs_threshold_per_item'] = isset($data['fs_threshold_per_item'])
            ? preg_replace('/\D/', '', (string) $data['fs_threshold_per_item']) : null;
        $data['fs_threshold_total'] = isset($data['fs_threshold_total'])
            ? preg_replace('/\D/', '', (string) $data['fs_threshold_total']) : null;
        // Normalize boolean radio to 0/1 if needed
        if (isset($data['fs_document_enabled'])) {
            $data['fs_document_enabled'] = in_array($data['fs_document_enabled'], ['1', 1, true, 'true'], true) ? 1 : 0;
        }

        $validator = Validator::make($data, [
            'fs_threshold_per_item' => 'required|integer|min:0',
            'fs_threshold_total' => 'required|integer|min:0',
            'fs_document_enabled' => 'required|boolean'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Use sanitized values
        $thresholdPerItem = (string) ($data['fs_threshold_per_item'] ?? '0');
        $thresholdTotal = (string) ($data['fs_threshold_total'] ?? '0');

        // Update main settings with group
        Setting::updateOrCreate(
            ['key' => 'fs_threshold_per_item'],
            ['value' => $thresholdPerItem, 'type' => 'integer', 'group' => 'approval_request']
        );
        Setting::updateOrCreate(
            ['key' => 'fs_threshold_total'],
            ['value' => $thresholdTotal, 'type' => 'integer', 'group' => 'approval_request']
        );
        Setting::updateOrCreate(
            ['key' => 'fs_document_enabled'],
            ['value' => ((int) $data['fs_document_enabled'] === 1) ? 'true' : 'false', 'type' => 'boolean', 'group' => 'approval_request']
        );

        // Update per-condition settings for Condition 1 (Item meets per-item threshold)
        Setting::updateOrCreate(
            ['key' => 'fs_per_item_show_form'],
            ['value' => $request->has('fs_per_item_show_form') ? 'true' : 'false', 'type' => 'boolean', 'group' => 'approval_request']
        );
        Setting::updateOrCreate(
            ['key' => 'fs_per_item_enable_input'],
            ['value' => $request->has('fs_per_item_enable_input') ? 'true' : 'false', 'type' => 'boolean', 'group' => 'approval_request']
        );
        Setting::updateOrCreate(
            ['key' => 'fs_per_item_enable_upload'],
            ['value' => $request->has('fs_per_item_enable_upload') ? 'true' : 'false', 'type' => 'boolean', 'group' => 'approval_request']
        );

        // Update per-condition settings for Condition 2 (Only total meets threshold)
        Setting::updateOrCreate(
            ['key' => 'fs_total_show_form'],
            ['value' => $request->has('fs_total_show_form') ? 'true' : 'false', 'type' => 'boolean', 'group' => 'approval_request']
        );
        Setting::updateOrCreate(
            ['key' => 'fs_total_enable_input'],
            ['value' => $request->has('fs_total_enable_input') ? 'true' : 'false', 'type' => 'boolean', 'group' => 'approval_request']
        );
        Setting::updateOrCreate(
            ['key' => 'fs_total_enable_upload'],
            ['value' => $request->has('fs_total_enable_upload') ? 'true' : 'false', 'type' => 'boolean', 'group' => 'approval_request']
        );

        // Clear cache for settings
        Setting::clearCache();

        return redirect()->route('settings.index')->with('success', 'Pengaturan berhasil diperbarui');
    }

    /**
     * Get settings for API (used by JavaScript)
     */
    public function getSettings()
    {
        $settings = Setting::getGroup('approval_request');
        
        return response()->json([
            'success' => true,
            'settings' => $settings
        ]);
    }
}
