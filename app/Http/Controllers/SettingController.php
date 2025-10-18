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
        $validator = Validator::make($request->all(), [
            'fs_threshold_per_item' => 'required|integer|min:0',
            'fs_threshold_total' => 'required|integer|min:0',
            'fs_document_enabled' => 'required|boolean'
        ]);

        if ($validator->fails()) {
            return redirect()->back()->withErrors($validator)->withInput();
        }

        // Update settings
        Setting::set('fs_threshold_per_item', $request->fs_threshold_per_item, 'integer');
        Setting::set('fs_threshold_total', $request->fs_threshold_total, 'integer');
        Setting::set('fs_document_enabled', $request->fs_document_enabled, 'boolean');

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
