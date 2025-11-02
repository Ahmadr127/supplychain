@props(['itemExtra'])

@if($itemExtra)
<div class="mt-2 border-t border-gray-200 pt-2">
    <div class="text-xs font-semibold text-gray-700 mb-1">Data Analisa Kebutuhan</div>
    
    <!-- Accordion for sections -->
    <div class="space-y-1" x-data="{ openA: false, openB: false, openC: false, openDE: false }">
        <!-- Section A: Identifikasi Kebutuhan -->
        @if($itemExtra->a_nama || $itemExtra->a_fungsi || $itemExtra->a_ukuran)
        <div class="border border-gray-200 rounded">
            <button @click="openA = !openA" 
                    type="button"
                    class="w-full px-2 py-1 text-left text-xs font-medium text-gray-700 bg-gray-50 hover:bg-gray-100 focus:outline-none">
                <div class="flex justify-between items-center">
                    <span>A. Identifikasi Kebutuhan Barang</span>
                    <svg class="w-3 h-3 transform transition-transform" :class="openA ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </div>
            </button>
            <div x-show="openA" x-collapse class="px-2 py-1 bg-white">
                <dl class="grid grid-cols-2 gap-x-2 gap-y-1 text-xs">
                    @foreach($itemExtra->getSectionADisplay() as $label => $value)
                        @if($value)
                        <div>
                            <dt class="text-gray-500">{{ $label }}:</dt>
                            <dd class="text-gray-900">{{ $value }}</dd>
                        </div>
                        @endif
                    @endforeach
                </dl>
            </div>
        </div>
        @endif
        
        <!-- Section B: Dukungan Unit -->
        @if($itemExtra->b_jml_pegawai || $itemExtra->b_jml_dokter || $itemExtra->b_beban)
        <div class="border border-gray-200 rounded">
            <button @click="openB = !openB" 
                    type="button"
                    class="w-full px-2 py-1 text-left text-xs font-medium text-gray-700 bg-gray-50 hover:bg-gray-100 focus:outline-none">
                <div class="flex justify-between items-center">
                    <span>B. Dukungan Unit</span>
                    <svg class="w-3 h-3 transform transition-transform" :class="openB ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </div>
            </button>
            <div x-show="openB" x-collapse class="px-2 py-1 bg-white">
                <dl class="grid grid-cols-2 gap-x-2 gap-y-1 text-xs">
                    @foreach($itemExtra->getSectionBDisplay() as $label => $value)
                        @if($value)
                        <div>
                            <dt class="text-gray-500">{{ $label }}:</dt>
                            <dd class="text-gray-900">{{ $value }}</dd>
                        </div>
                        @endif
                    @endforeach
                </dl>
            </div>
        </div>
        @endif
        
        <!-- Section C: Identifikasi Barang Eksisting -->
        @if($itemExtra->c_jumlah || $itemExtra->c_lokasi || $itemExtra->c_kondisi)
        <div class="border border-gray-200 rounded">
            <button @click="openC = !openC" 
                    type="button"
                    class="w-full px-2 py-1 text-left text-xs font-medium text-gray-700 bg-gray-50 hover:bg-gray-100 focus:outline-none">
                <div class="flex justify-between items-center">
                    <span>C. Identifikasi Barang Eksisting</span>
                    <svg class="w-3 h-3 transform transition-transform" :class="openC ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </div>
            </button>
            <div x-show="openC" x-collapse class="px-2 py-1 bg-white">
                <dl class="grid grid-cols-2 gap-x-2 gap-y-1 text-xs">
                    @foreach($itemExtra->getSectionCDisplay() as $label => $value)
                        @if($value && $value !== '-')
                        <div>
                            <dt class="text-gray-500">{{ $label }}:</dt>
                            <dd class="text-gray-900">{{ $value }}</dd>
                        </div>
                        @endif
                    @endforeach
                </dl>
            </div>
        </div>
        @endif
        
        <!-- Section D/E: Persyaratan & Operasional -->
        @if($itemExtra->e_kirim || $itemExtra->e_angkut || $itemExtra->e_instalasi || $itemExtra->e_operasi)
        <div class="border border-gray-200 rounded">
            <button @click="openDE = !openDE" 
                    type="button"
                    class="w-full px-2 py-1 text-left text-xs font-medium text-gray-700 bg-gray-50 hover:bg-gray-100 focus:outline-none">
                <div class="flex justify-between items-center">
                    <span>D/E. Persyaratan & Operasional</span>
                    <svg class="w-3 h-3 transform transition-transform" :class="openDE ? 'rotate-180' : ''" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"></path>
                    </svg>
                </div>
            </button>
            <div x-show="openDE" x-collapse class="px-2 py-1 bg-white">
                <dl class="grid grid-cols-2 gap-x-2 gap-y-1 text-xs">
                    @foreach($itemExtra->getSectionDEDisplay() as $label => $value)
                        @if($value && $value !== '-')
                        <div>
                            <dt class="text-gray-500">{{ $label }}:</dt>
                            <dd class="text-gray-900">{{ $value }}</dd>
                        </div>
                        @endif
                    @endforeach
                </dl>
            </div>
        </div>
        @endif
    </div>
</div>
@endif
