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
                                class="pl-10 w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                placeholder="100.000.000">
                        </div>
                        <p class="text-xs text-gray-500 mt-1">
                            Jika subtotal item (jumlah × harga satuan) mencapai atau melebihi nilai ini, form FS akan muncul untuk item tersebut
                        </p>
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
                                class="pl-10 w-full border border-gray-300 rounded-md px-3 py-2 focus:outline-none focus:ring-2 focus:ring-blue-500"
                                placeholder="500.000.000">
                        </div>
                        <p class="text-xs text-gray-500 mt-1">
                            Jika total harga semua item dalam pengajuan mencapai atau melebihi nilai ini, upload dokumen FS akan diperlukan
                        </p>
                    </div>

                    <!-- Information Box -->
                    <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                        <h3 class="text-sm font-semibold text-blue-800 mb-2">Informasi Dokumen FS</h3>
                        <ul class="text-xs text-blue-700 space-y-1">
                            <li>• Dokumen FS (Feasibility Study) diperlukan untuk pengajuan dengan nilai tinggi</li>
                            <li>• Form FS akan muncul otomatis per item jika subtotal item mencapai threshold</li>
                            <li>• Upload dokumen FS akan diperlukan jika total pengajuan mencapai threshold total</li>
                            <li>• Perubahan pengaturan akan berlaku untuk pengajuan baru</li>
                        </ul>
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
    // Format rupiah input
    document.addEventListener('DOMContentLoaded', function() {
        const rupiahInputs = ['fs_threshold_per_item', 'fs_threshold_total'];
        
        rupiahInputs.forEach(id => {
            const input = document.getElementById(id);
            if (input) {
                input.addEventListener('input', function(e) {
                    let value = e.target.value.replace(/\D/g, '');
                    if (value) {
                        value = parseInt(value).toLocaleString('id-ID');
                        e.target.value = value.replace(/,/g, '.');
                    }
                });
                
                // Clean value before submit
                const form = input.closest('form');
                form.addEventListener('submit', function() {
                    input.value = input.value.replace(/\./g, '');
                });
            }
        });
    });
</script>
@endsection
