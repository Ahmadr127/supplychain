@extends('layouts.app')

@section('title', 'Detail Technical Support')

@section('content')
<div class="mb-4 flex items-center justify-between">
    <div>
        <a href="{{ route('technical-support.index') }}" class="text-blue-600 hover:text-blue-800 text-sm flex items-center">
            <i class="fas fa-arrow-left mr-1"></i> Kembali ke Antrean TS
        </a>
    </div>
</div>

<div class="grid grid-cols-1 md:grid-cols-3 gap-6">
    <!-- Informasi Request & Item -->
    <div class="md:col-span-1 space-y-6">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 rounded-t-lg">
                <h3 class="text-lg font-semibold text-gray-800">Info Item</h3>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    <div>
                        <span class="text-sm text-gray-500 block mb-1">Item Master</span>
                        <div class="font-medium text-gray-900">{{ $item->masterItem->name ?? '-' }}</div>
                        <div class="text-xs text-gray-500 mt-0.5">Kategori: {{ $item->masterItem->itemCategory->name ?? '-' }}</div>
                    </div>
                    
                    <div>
                        <span class="text-sm text-gray-500 block mb-1">Kuantitas</span>
                        <div class="font-medium text-gray-900">{{ $item->quantity }} {{ $item->masterItem->unit->name ?? 'Unit' }}</div>
                    </div>

                    @if($item->brand)
                    <div>
                        <span class="text-sm text-gray-500 block mb-1">Brand / Merek</span>
                        <div class="font-medium text-gray-900">{{ $item->brand }}</div>
                    </div>
                    @endif

                    @if($item->specification)
                    <div>
                        <span class="text-sm text-gray-500 block mb-1">Spesifikasi Awal (Dari Requester)</span>
                        <div class="text-gray-800 text-sm whitespace-pre-wrap bg-gray-50 p-3 rounded border border-gray-200">{{ $item->specification }}</div>
                    </div>
                    @endif
                </div>
            </div>
        </div>

        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200 bg-gray-50 rounded-t-lg">
                <h3 class="text-lg font-semibold text-gray-800">Info Request</h3>
            </div>
            <div class="p-6">
                <div class="space-y-4">
                    <div>
                        <span class="text-sm text-gray-500 block mb-1">No. Request</span>
                        <div class="font-medium text-gray-900">{{ $item->approvalRequest->request_number }}</div>
                    </div>
                    
                    <div>
                        <span class="text-sm text-gray-500 block mb-1">Requester</span>
                        <div class="font-medium text-gray-900">{{ $item->approvalRequest->requester->name ?? '-' }}</div>
                    </div>

                    <div>
                        <span class="text-sm text-gray-500 block mb-1">Tanggal Request</span>
                        <div class="font-medium text-gray-900">{{ $item->approvalRequest->created_at->format('d F Y H:i') }}</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Form Technical Support -->
    <div class="md:col-span-2">
        <div class="bg-white rounded-lg shadow-sm border border-gray-200">
            <div class="px-6 py-4 border-b border-gray-200 bg-blue-50 rounded-t-lg">
                <h3 class="text-lg font-semibold text-blue-800">
                    <i class="fas fa-tools mr-2"></i> Input Spesifikasi Technical Support
                </h3>
            </div>
            
            <div class="p-6">
                @if($item->ts_status === 'done')
                    <div class="bg-green-50 border-l-4 border-green-500 p-4 mb-6">
                        <div class="flex items-center">
                            <i class="fas fa-check-circle text-green-500 mr-3 text-xl"></i>
                            <div>
                                <h4 class="text-green-800 font-medium">Spesifikasi TS Telah Diisi</h4>
                                <p class="text-sm text-green-600 mt-1">Anda sudah memberikan spesifikasi untuk item ini. Anda dapat memperbaruinya di bawah ini.</p>
                            </div>
                        </div>
                    </div>
                @else
                    <div class="bg-yellow-50 border-l-4 border-yellow-500 p-4 mb-6">
                        <div class="flex items-start">
                            <i class="fas fa-info-circle text-yellow-500 mr-3 mt-0.5"></i>
                            <div>
                                <h4 class="text-yellow-800 font-medium">Menunggu Spesifikasi TS</h4>
                                <p class="text-sm text-yellow-700 mt-1">
                                    Item ini diidentifikasi membutuhkan spesifikasi teknis tambahan. Silakan lengkapi form di bawah ini agar proses pengajuan dapat dilanjutkan dengan spesifikasi yang tepat.
                                </p>
                            </div>
                        </div>
                    </div>
                @endif

                <form action="{{ route('technical-support.update', $item->id) }}" method="POST">
                    @csrf
                    @method('PUT')
                    
                    <div class="mb-6">
                        <label for="ts_specification" class="block text-sm font-medium text-gray-700 mb-2">
                            Spesifikasi Lengkap / Rekomendasi Teknis <span class="text-red-500">*</span>
                        </label>
                        <textarea name="ts_specification" id="ts_specification" rows="10" required
                                  class="w-full px-4 py-3 border border-gray-300 rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500"
                                  placeholder="Masukkan spesifikasi teknis lengkap, rekomendasi brand, atau catatan teknis lainnya di sini...">{{ old('ts_specification', $item->ts_specification) }}</textarea>
                        @error('ts_specification')
                            <p class="text-sm text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="flex justify-end gap-3">
                        <a href="{{ route('technical-support.index') }}" class="px-5 py-2.5 bg-white border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 font-medium transition-colors">
                            Batal
                        </a>
                        <button type="submit" class="px-5 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 font-medium transition-colors shadow-sm">
                            <i class="fas fa-save mr-2"></i> Simpan Spesifikasi
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
