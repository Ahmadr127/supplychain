@extends('layouts.app')

@section('title', 'Import Users')

@section('content')
<div class="max-w-4xl mx-auto">
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 bg-white border-b border-gray-200">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-900">Import Users dari Excel</h2>
                <a href="{{ route('users.index') }}" class="text-gray-600 hover:text-gray-900">
                    <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </a>
            </div>

            @if(session('success'))
                <div class="mb-4 bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded">
                    {{ session('success') }}
                </div>
            @endif

            @if(session('error'))
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    {{ session('error') }}
                </div>
            @endif

            @if(session('warning'))
                <div class="mb-4 bg-yellow-100 border border-yellow-400 text-yellow-700 px-4 py-3 rounded">
                    {{ session('warning') }}
                </div>
            @endif

            @if($errors->any())
                <div class="mb-4 bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded">
                    <strong>Terjadi kesalahan:</strong>
                    <ul class="list-disc list-inside mt-2">
                        @foreach($errors->all() as $error)
                            <li>{{ $error }}</li>
                        @endforeach
                    </ul>
                </div>
            @endif

            <!-- Download Template -->
            <div class="mb-6 p-4 bg-blue-50 border border-blue-200 rounded-lg">
                <div class="flex items-start">
                    <svg class="w-6 h-6 text-blue-600 mr-3 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div class="flex-1">
                        <h3 class="text-lg font-semibold text-blue-900 mb-2">Download Template Excel</h3>
                        <p class="text-sm text-blue-800 mb-3">
                            Download template Excel terlebih dahulu, isi data user sesuai format, lalu upload kembali.
                        </p>
                        <a href="{{ route('users.import.template') }}" 
                           class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                            </svg>
                            Download Template
                        </a>
                    </div>
                </div>
            </div>

            <!-- Format Info -->
            <div class="mb-6 p-4 bg-gray-50 border border-gray-200 rounded-lg">
                <h3 class="text-lg font-semibold text-gray-900 mb-3">Format File Excel:</h3>
                <div class="overflow-x-auto">
                    <table class="min-w-full text-sm">
                        <thead class="bg-gray-100">
                            <tr>
                                <th class="px-4 py-2 text-left font-semibold">NO</th>
                                <th class="px-4 py-2 text-left font-semibold">NIP</th>
                                <th class="px-4 py-2 text-left font-semibold">Nama Karyawan</th>
                                <th class="px-4 py-2 text-left font-semibold">Organisasi</th>
                                <th class="px-4 py-2 text-left font-semibold">Posisi Pekerjaan</th>
                                <th class="px-4 py-2 text-left font-semibold">Jabatan</th>
                            </tr>
                        </thead>
                        <tbody class="bg-white">
                            <tr class="border-t">
                                <td class="px-4 py-2">1</td>
                                <td class="px-4 py-2">20141969</td>
                                <td class="px-4 py-2">DIENI ANANDA PUTRI</td>
                                <td class="px-4 py-2">MUTU</td>
                                <td class="px-4 py-2">MANAGER MUTU</td>
                                <td class="px-4 py-2">MANAGER</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
                <div class="mt-3 text-sm text-gray-600">
                    <p class="mb-1"><strong>Catatan:</strong></p>
                    <ul class="list-disc list-inside space-y-1">
                        <li>Kolom <strong>NIP</strong> dan <strong>Nama Karyawan</strong> wajib diisi</li>
                        <li>Jika <strong>Jabatan</strong> belum ada, akan dibuat otomatis dengan permission staff</li>
                        <li>Password default untuk semua user: <code class="bg-gray-200 px-2 py-1 rounded">password</code></li>
                        <li>Username akan dibuat otomatis dari nama (huruf kecil, spasi diganti titik)</li>
                    </ul>
                </div>
            </div>

            <!-- Upload Form -->
            <form action="{{ route('users.import.process') }}" method="POST" enctype="multipart/form-data" class="space-y-6" id="importForm" novalidate>
                @csrf

                <!-- File Upload -->
                <div>
                    <label class="block text-sm font-medium text-gray-700 mb-2">
                        Upload File Excel <span class="text-red-500">*</span>
                    </label>
                    <input type="file" 
                           name="file" 
                           accept=".xlsx,.xls,.csv"
                           required
                           class="block w-full text-sm text-gray-900 border border-gray-300 rounded-lg cursor-pointer bg-gray-50 focus:outline-none focus:border-blue-500 @error('file') border-red-500 @enderror"
                           id="fileInput">
                    @error('file')
                        <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                    @enderror
                </div>

                <!-- Toggle Kepala Organisasi -->
                <div class="flex items-start">
                    <div class="flex items-center h-5">
                        <input type="checkbox" 
                               name="set_as_head" 
                               id="set_as_head" 
                               value="1"
                               class="w-4 h-4 text-blue-600 bg-gray-100 border-gray-300 rounded focus:ring-blue-500">
                    </div>
                    <div class="ml-3">
                        <label for="set_as_head" class="font-medium text-gray-900">
                            Set sebagai Kepala Organisasi
                        </label>
                        <p class="text-sm text-gray-500">
                            Jika dicentang, semua user yang diimport akan menjadi kepala/manager dari organisasi mereka
                        </p>
                    </div>
                </div>

                <!-- Submit Button -->
                <div class="flex items-center justify-end space-x-3">
                    <a href="{{ route('users.index') }}" 
                       class="px-4 py-2 bg-gray-200 hover:bg-gray-300 text-gray-800 font-semibold rounded-lg"
                       id="cancelBtn">
                        Batal
                    </a>
                    <button type="submit" 
                            class="px-6 py-2 bg-green-600 hover:bg-green-700 text-white font-semibold rounded-lg flex items-center"
                            id="submitBtn">
                        <span id="submitText">Import Users</span>
                        <span id="submitSpinner" class="hidden ml-2">
                            <svg class="animate-spin h-5 w-5" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24">
                                <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                                <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4zm2 5.291A7.962 7.962 0 014 12H0c0 3.042 1.135 5.824 3 7.938l3-2.647z"></path>
                            </svg>
                        </span>
                    </button>
                </div>
            </form>

            <!-- Loading Modal -->
            <div id="loadingModal" class="hidden fixed inset-0 bg-black bg-opacity-50 flex items-center justify-center z-50">
                <div class="bg-white rounded-lg p-8 max-w-md w-full mx-4">
                    <div class="flex flex-col items-center">
                        <!-- Spinner Animation -->
                        <div class="relative w-16 h-16 mb-4">
                            <div class="absolute inset-0 rounded-full border-4 border-gray-200"></div>
                            <div class="absolute inset-0 rounded-full border-4 border-transparent border-t-green-600 border-r-green-600 animate-spin"></div>
                        </div>
                        
                        <h3 class="text-lg font-semibold text-gray-900 mb-2">Sedang Mengimport Data</h3>
                        <p class="text-sm text-gray-600 text-center mb-4">Mohon tunggu, sistem sedang memproses file Anda...</p>
                        
                        <!-- Progress Bar -->
                        <div class="w-full bg-gray-200 rounded-full h-2 overflow-hidden">
                            <div class="bg-green-600 h-full rounded-full animate-pulse" style="width: 100%;"></div>
                        </div>
                        
                        <p class="text-xs text-gray-500 mt-3">Jangan tutup halaman ini</p>
                    </div>
                </div>
            </div>

            <script>
                document.getElementById('importForm').addEventListener('submit', function(e) {
                    const fileInput = document.getElementById('fileInput');
                    
                    // Check if file is selected
                    if (!fileInput.files || fileInput.files.length === 0) {
                        e.preventDefault();
                        alert('Pilih file terlebih dahulu');
                        fileInput.focus();
                        return false;
                    }

                    // Show loading modal and spinner
                    document.getElementById('loadingModal').classList.remove('hidden');
                    document.getElementById('submitText').classList.add('hidden');
                    document.getElementById('submitSpinner').classList.remove('hidden');
                    document.getElementById('submitBtn').disabled = true;
                    document.getElementById('cancelBtn').classList.add('pointer-events-none', 'opacity-50');
                    
                    // Allow form to submit normally
                    return true;
                });
            </script>
        </div>
    </div>
</div>
@endsection
