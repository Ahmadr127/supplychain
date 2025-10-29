@props(['status', 'requestId', 'benchmarkNotes' => ''])

@php
    $ps = $status ?? 'unprocessed';
    $psText = match($ps){
        'unprocessed' => 'Belum diproses',
        'benchmarking' => 'Pemilihan vendor',
        'selected' => 'Proses PR & PO',
        'po_issued' => 'Proses di vendor',
        'grn_received' => 'Barang sudah diterima',
        'done' => 'Selesai',
        default => strtoupper($ps),
    };
    
    // Colors per request: benchmarking=red, selected=yellow, po_issued=orange, grn_received=green (white text)
    $psColor = match($ps){
        'benchmarking' => 'bg-red-600 text-white',
        'selected' => 'bg-yellow-400 text-black',
        'po_issued' => 'bg-orange-500 text-white',
        'grn_received' => 'bg-green-600 text-white',
        'unprocessed' => 'bg-gray-200 text-gray-800',
        'done' => 'bg-green-700 text-white',
        default => 'bg-gray-200 text-gray-800',
    };
@endphp

<span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium {{ $psColor }} cursor-pointer" 
      @if($benchmarkNotes) data-bmnotes="{{ e($benchmarkNotes) }}" @endif
      onclick="openPurchasingStatusModal('{{ $ps }}','{{ $psText }}','{{ $requestId }}'{{ $benchmarkNotes ? ', this' : '' }})">
    {{ $psText }}
</span>
