@props(['class' => '', 'variant' => 'both', 'size' => 'md'])

@php
    $isSmall = $size === 'sm';
    $containerGap = $isSmall ? 'gap-1' : 'gap-2';
    $textSize = $isSmall ? 'text-[11px]' : 'text-xs';
    $badgePad = $isSmall ? 'px-1 py-0.5' : 'px-1.5 py-0.5';
@endphp

<div class="w-full flex items-center {{ $containerGap }} {{ $textSize }} {{ $class }} overflow-x-auto whitespace-nowrap">
    @if($variant === 'status' || $variant === 'both')
        <span class="inline-flex items-center {{ $badgePad }} ">Informasi progress :</span>
        <span class="inline-flex items-center {{ $badgePad }} rounded bg-blue-500 text-white">On Progress</span>
        <span class="inline-flex items-center {{ $badgePad }} rounded bg-yellow-500 text-white">Pending/Waiting</span>
        <span class="inline-flex items-center {{ $badgePad }} rounded bg-green-600 text-white">Approved</span>
        <span class="inline-flex items-center {{ $badgePad }} rounded bg-red-600 text-white">Rejected</span>
        <span class="inline-flex items-center {{ $badgePad }} rounded bg-gray-500 text-white">Cancelled</span>
    @endif

    @if($variant === 'both')
        <span class="mx-2 text-gray-400">•</span>
    @endif

    @if($variant === 'purchasing' || $variant === 'both')
        <span class="inline-flex items-center {{ $badgePad }} rounded bg-gray-200 text-gray-800">Belum diproses</span>
        <span class="inline-flex items-center {{ $badgePad }} rounded bg-red-600 text-white">Pemilihan vendor</span>
        <span class="inline-flex items-center {{ $badgePad }} rounded bg-yellow-400 text-black">Uji coba/PR Sistem</span>
        <span class="inline-flex items-center {{ $badgePad }} rounded bg-orange-500 text-white">Proses di vendor</span>
        <span class="inline-flex items-center {{ $badgePad }} rounded bg-green-600 text-white">Barang diterima</span>
        <span class="inline-flex items-center {{ $badgePad }} rounded bg-green-700 text-white">Selesai</span>
    @endif
</div>
