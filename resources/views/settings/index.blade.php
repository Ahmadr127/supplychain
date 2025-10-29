@extends('layouts.app')
@section('title', 'Pengaturan Dokumen FS')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="bg-white rounded-lg shadow-md">
        <div class="p-6">
            <form action="{{ route('settings.update') }}" method="POST">
                @csrf
                @method('PUT')
                
                <div class="space-y-6">
                    <!-- Enable/Disable FS Document -->
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">
                            Status Fitur Dokumen FS
                        </label>
                        <div class="flex items-center space-x-4">
                            <label class="flex items-center">
                                <input type="radio" name="fs_document_enabled" value="1" 
                                    {{ ($settings['fs_document_enabled'] ?? true) ? 'checked' : '' }}
                                    class="mr-2">
                                <span>Aktif</span>
                            </label>
                            <label class="flex items-center">
                                <input type="radio" name="fs_document_enabled" value="0"
                                    {{ !($settings['fs_document_enabled'] ?? true) ? 'checked' : '' }}
                                    class="mr-2">
                                <span>Nonaktif</span>
                            </label>
                        </div>
                        <p class="text-xs text-gray-500 mt-1">
                            Mengaktifkan atau menonaktifkan fitur dokumen FS pada form pengajuan
                        </p>
                    </div>

                    <!-- Threshold Tampil Form (Show Threshold) -->
                    <div>
                        <label for="fs_threshold_per_item" class="block text-sm font-medium text-gray-700 mb-2">
                            Threshold Tampil Form FS (Rupiah)
                        </label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500">Rp</span>
                            <input type="text" 
                                id="fs_threshold_per_item" 
                                name="fs_threshold_per_item" 
                                value="{{ number_format($settings['fs_threshold_per_item'] ?? 50000000, 0, ',', '.') }}"
                                class="fs-threshold pl-10 w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                placeholder="50.000.000">
                        </div>
                        <div class="mt-2 flex flex-wrap gap-2">
                            <button type="button" class="quick-btn px-2 py-1 text-xs border rounded" data-target="fs_threshold_per_item" data-value="30000000">30 Jt</button>
                            <button type="button" class="quick-btn px-2 py-1 text-xs border rounded" data-target="fs_threshold_per_item" data-value="50000000">50 Jt</button>
                            <button type="button" class="quick-btn px-2 py-1 text-xs border rounded" data-target="fs_threshold_per_item" data-value="75000000">75 Jt</button>
                            <button type="button" class="quick-btn px-2 py-1 text-xs border rounded" data-target="fs_threshold_per_item" data-value="100000000">100 Jt</button>
                        </div>
                        <div class="mt-3 p-3 bg-blue-50 border border-blue-200 rounded-md">
                            <p class="text-xs text-blue-800">
                                <i class="fas fa-info-circle mr-1"></i>
                                Ketika subtotal item ≥ nilai ini, form FS akan ditampilkan dan input dapat diisi.
                            </p>
                        </div>
                    </div>

                    <!-- Threshold Upload Dokumen -->
                    <div>
                        <label for="fs_threshold_total" class="block text-sm font-medium text-gray-700 mb-2">
                            Threshold Upload Dokumen FS (Rupiah)
                        </label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500">Rp</span>
                            <input type="text" 
                                id="fs_threshold_total" 
                                name="fs_threshold_total" 
                                value="{{ number_format($settings['fs_threshold_total'] ?? 100000000, 0, ',', '.') }}"
                                class="fs-threshold pl-10 w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                placeholder="100.000.000">
                        </div>
                        <div class="mt-2 flex flex-wrap gap-2">
                            <button type="button" class="quick-btn px-2 py-1 text-xs border rounded" data-target="fs_threshold_total" data-value="75000000">75 Jt</button>
                            <button type="button" class="quick-btn px-2 py-1 text-xs border rounded" data-target="fs_threshold_total" data-value="100000000">100 Jt</button>
                            <button type="button" class="quick-btn px-2 py-1 text-xs border rounded" data-target="fs_threshold_total" data-value="200000000">200 Jt</button>
                            <button type="button" class="quick-btn px-2 py-1 text-xs border rounded" data-target="fs_threshold_total" data-value="500000000">500 Jt</button>
                        </div>
                        <div class="mt-3 p-3 bg-yellow-50 border border-yellow-200 rounded-md">
                            <p class="text-xs text-yellow-800">
                                <i class="fas fa-exclamation-triangle mr-1"></i>
                                Ketika subtotal item ≥ nilai ini, upload dokumen FS <strong>wajib</strong> dilakukan.
                            </p>
                        </div>
                        <!-- Preserve old settings for backward compatibility -->
                        <input type="hidden" name="fs_per_item_show_form" value="1">
                        <input type="hidden" name="fs_per_item_enable_input" value="1">
                        <input type="hidden" name="fs_per_item_enable_upload" value="1">
                        <input type="hidden" name="fs_total_show_form" value="1">
                        <input type="hidden" name="fs_total_enable_input" value="1">
                        <input type="hidden" name="fs_total_enable_upload" value="1">
                        <input type="hidden" name="fs_upload_show_form" value="1">
                        <input type="hidden" name="fs_upload_enable_input" value="1">
                        <input type="hidden" name="fs_upload_enable_upload" value="1">
                    </div>

                    

                    <!-- Submit Button -->
                    <div class="flex justify-end space-x-3">
                        <a href="{{ route('dashboard') }}" 
                            class="px-6 py-2 border border-gray-300 rounded-md text-gray-700 hover:bg-gray-50 transition-colors">
                            Batal
                        </a>
                        <button type="submit" 
                            class="px-6 py-2 bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">
                            Simpan Pengaturan
                        </button>
                    </div>
                    
                    <!-- Keterangan dan Contoh Kasus -->
                    <div class="mt-4 p-4 bg-gradient-to-r from-blue-50 to-green-50 border border-blue-200 rounded-lg">
                        <div class="text-sm font-semibold text-gray-800 mb-3">
                            <i class="fas fa-lightbulb mr-2 text-yellow-500"></i>
                            Cara Kerja Threshold
                        </div>
                        <div class="space-y-3 text-xs text-gray-700">
                            <div class="flex items-start">
                                <span class="inline-block w-24 font-semibold text-gray-600 flex-shrink-0">Contoh 1:</span>
                                <span>Subtotal 60 jt (≥ 50 jt tapi < 100 jt) → Form FS tampil, input aktif, <strong>upload tidak wajib</strong></span>
                            </div>
                            <div class="flex items-start">
                                <span class="inline-block w-24 font-semibold text-gray-600 flex-shrink-0">Contoh 2:</span>
                                <span>Subtotal 120 jt (≥ 100 jt) → Form FS tampil, input aktif, <strong>upload wajib</strong></span>
                            </div>
                            <div class="flex items-start">
                                <span class="inline-block w-24 font-semibold text-gray-600 flex-shrink-0">Contoh 3:</span>
                                <span>Subtotal 30 jt (< 50 jt) → Form FS <strong>tidak tampil</strong></span>
                            </div>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
    // Helpers
    function formatRupiahNumber(num) {
        try { return (parseInt(num || 0) || 0).toLocaleString('id-ID').replace(/,/g, '.'); } catch(_) { return '0'; }
    }
    document.addEventListener('DOMContentLoaded', function() {
            const form = document.querySelector('form');
            const thresholdInputs = Array.from(document.querySelectorAll('.fs-threshold'));
            const behaviorInputs = Array.from(document.querySelectorAll('.fs-behavior'));
            const enabledRadios = Array.from(document.querySelectorAll('input[name="fs_document_enabled"]'));

            // (mirror logic removed; sections are independent now)

        // Format while typing
        thresholdInputs.forEach(input => {
            input.addEventListener('input', function(e) {
                let value = e.target.value.replace(/\D/g, '');
                e.target.value = formatRupiahNumber(value);
            });
        });

        // Quick-set buttons
        document.querySelectorAll('.quick-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                const target = document.getElementById(this.dataset.target);
                const val = parseInt(this.dataset.value || '0');
                if (target) target.value = formatRupiahNumber(val);
            });
        });

        // Toggle disable behavior when disabled
        function applyEnabledState() {
            const enabled = (document.querySelector('input[name="fs_document_enabled"][value="1"]').checked);
            behaviorInputs.forEach(el => {
                el.disabled = !enabled;
                if (!enabled) {
                    el.closest('label')?.classList.add('opacity-60');
                } else {
                    el.closest('label')?.classList.remove('opacity-60');
                }
            });
        }
        enabledRadios.forEach(r => r.addEventListener('change', applyEnabledState));
        applyEnabledState();

        // (duplicate mirror sync block removed)

        // Clean values before submit once
        if (form) {
            form.addEventListener('submit', function() {
                thresholdInputs.forEach(input => {
                    input.value = (input.value || '').replace(/\./g, '');
                });
            });
        }
    });
</script>
@endsection
