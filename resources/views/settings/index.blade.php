@extends('layouts.app')

@section('title', 'Pengaturan Dokumen FS')

@section('content')
<div class="container mx-auto px-4 py-6">
    <div class="bg-white rounded-lg shadow-md">
        <div class="p-6">
            <h2 class="text-2xl font-bold mb-6">Pengaturan Dokumen Feasibility Study (FS)</h2>
            
            @if(session('success'))
                <div class="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
                    {{ session('success') }}
                </div>
            @endif

            @if($errors->any())
                <div class="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
                    <ul>
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

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

                    <!-- Threshold per Item -->
                    <div>
                        <label for="fs_threshold_per_item" class="block text-sm font-medium text-gray-700 mb-2">
                            Threshold Harga Per Item (Rupiah)
                        </label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500">Rp</span>
                            <input type="text" 
                                id="fs_threshold_per_item" 
                                name="fs_threshold_per_item" 
                                value="{{ number_format($settings['fs_threshold_per_item'] ?? 100000000, 0, ',', '.') }}"
                                class="fs-threshold pl-10 w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                placeholder="100.000.000">
                        </div>
                        <div class="mt-2 flex flex-wrap gap-2">
                            <button type="button" class="quick-btn px-2 py-1 text-xs border rounded" data-target="fs_threshold_per_item" data-value="50000000">50 Jt</button>
                            <button type="button" class="quick-btn px-2 py-1 text-xs border rounded" data-target="fs_threshold_per_item" data-value="100000000">100 Jt</button>
                            <button type="button" class="quick-btn px-2 py-1 text-xs border rounded" data-target="fs_threshold_per_item" data-value="200000000">200 Jt</button>
                            <button type="button" class="quick-btn px-2 py-1 text-xs border rounded" data-target="fs_threshold_per_item" data-value="500000000">500 Jt</button>
                        </div>
                        <div class="mt-3 p-3 bg-green-50 border border-green-200 rounded-md">
                            <div class="text-xs font-medium text-green-800 mb-2">
                                Perilaku saat memenuhi threshold per-item
                            </div>
                            <div class="space-y-2">
                                <label class="flex items-center">
                                    <input type="checkbox" name="fs_per_item_show_form" value="1" 
                                        {{ ($settings['fs_per_item_show_form'] ?? true) ? 'checked' : '' }}
                                        class="fs-behavior mr-2">
                                    <span class="text-xs">Tampilkan form FS</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="fs_per_item_enable_input" value="1" 
                                        {{ ($settings['fs_per_item_enable_input'] ?? true) ? 'checked' : '' }}
                                        class="fs-behavior mr-2">
                                    <span class="text-xs">Aktifkan input form (bisa diisi)</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="fs_per_item_enable_upload" value="1" 
                                        {{ ($settings['fs_per_item_enable_upload'] ?? true) ? 'checked' : '' }}
                                        class="fs-behavior mr-2">
                                    <span class="text-xs">Aktifkan upload dokumen FS</span>
                                </label>
                                <p class="text-xs text-gray-500 mt-1">
                                    Jika subtotal item (jumlah × harga satuan) ≥ nilai ini, pengaturan di atas akan diterapkan untuk item tersebut.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Threshold Total -->
                    <div>
                        <label for="fs_threshold_total" class="block text-sm font-medium text-gray-700 mb-2">
                            Threshold Total Semua Item (Rupiah)
                        </label>
                        <div class="relative">
                            <span class="absolute left-3 top-1/2 transform -translate-y-1/2 text-gray-500">Rp</span>
                            <input type="text" 
                                id="fs_threshold_total" 
                                name="fs_threshold_total" 
                                value="{{ number_format($settings['fs_threshold_total'] ?? 500000000, 0, ',', '.') }}"
                                class="fs-threshold pl-10 w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                placeholder="500.000.000">
                        </div>
                        <div class="mt-2 flex flex-wrap gap-2">
                            <button type="button" class="quick-btn px-2 py-1 text-xs border rounded" data-target="fs_threshold_total" data-value="250000000">250 Jt</button>
                            <button type="button" class="quick-btn px-2 py-1 text-xs border rounded" data-target="fs_threshold_total" data-value="500000000">500 Jt</button>
                            <button type="button" class="quick-btn px-2 py-1 text-xs border rounded" data-target="fs_threshold_total" data-value="1000000000">1 M</button>
                            <button type="button" class="quick-btn px-2 py-1 text-xs border rounded" data-target="fs_threshold_total" data-value="2000000000">2 M</button>
                        </div>
                        <div class="mt-3 p-3 bg-yellow-50 border border-yellow-200 rounded-md">
                            <div class="text-xs font-medium text-yellow-800 mb-2">
                                Perilaku saat hanya total memenuhi threshold
                            </div>
                            <div class="space-y-2">
                                <label class="flex items-center">
                                    <input type="checkbox" name="fs_total_show_form" value="1" 
                                        {{ ($settings['fs_total_show_form'] ?? true) ? 'checked' : '' }}
                                        class="fs-behavior mr-2">
                                    <span class="text-xs">Tampilkan form FS</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="fs_total_enable_input" value="1" 
                                        {{ ($settings['fs_total_enable_input'] ?? false) ? 'checked' : '' }}
                                        class="fs-behavior mr-2">
                                    <span class="text-xs">Aktifkan input form (bisa diisi)</span>
                                </label>
                                <label class="flex items-center">
                                    <input type="checkbox" name="fs_total_enable_upload" value="1" 
                                        {{ ($settings['fs_total_enable_upload'] ?? false) ? 'checked' : '' }}
                                        class="fs-behavior mr-2">
                                    <span class="text-xs">Aktifkan upload dokumen FS</span>
                                </label>
                                <p class="text-xs text-gray-500 mt-1">
                                    Jika total pengajuan ≥ nilai ini dan item tertentu belum memenuhi threshold per-item, pengaturan di atas diterapkan.
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Info: Tidak memenuhi threshold -->
                    <div class="p-3 bg-gray-50 border border-gray-200 rounded-md">
                        <h4 class="text-sm font-medium text-gray-700 mb-2">
                            <i class="fas fa-times-circle mr-1"></i>
                            Kondisi 3: Tidak Memenuhi Threshold
                        </h4>
                        <p class="text-xs text-gray-600 mb-2">
                            Ketika item dan total di bawah threshold yang ditentukan, form FS tidak ditampilkan.
                        </p>
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
