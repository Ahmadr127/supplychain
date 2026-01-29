@props(['status' => null, 'requestId' => null, 'benchmarkNotes' => '', 'item' => null, 'request' => null])

@php
    $finalStatus = $status;
    $finalRequestId = $requestId;
    $finalBmNotes = $benchmarkNotes;

    // If item and request are provided, determine status dynamically
    if ($item && $request) {
        $finalRequestId = $request->id;
        
        // Try to find purchasing item
        // Note: This query inside a loop is not ideal for performance (N+1), 
        // but necessary given current relationship structure.
        $pi = \App\Models\PurchasingItem::where('approval_request_id', $request->id)
                ->where('master_item_id', $item->master_item_id)
                ->first();
        
        if ($pi) {
            $finalStatus = $pi->status;
            // Get benchmark notes if available
            if ($pi->benchmark_notes) {
                $itemName = $item->masterItem->name ?? 'Item';
                $note = trim(preg_replace('/\s+/', ' ', (string)$pi->benchmark_notes));
                $finalBmNotes = "$itemName: $note";
            }
        } elseif (in_array($item->status, ['in_purchasing', 'approved', 'in_release'])) {
            $finalStatus = 'unprocessed';
        } else {
            $finalStatus = 'pending_approval';
        }
    }

    $ps = $finalStatus ?? 'unprocessed';
    $psText = match($ps){
        'unprocessed' => 'Belum diproses',
        'benchmarking' => 'Pemilihan vendor',
        'selected' => 'Proses PR & PO',
        'po_issued' => 'Proses di vendor',
        'grn_received' => 'Barang sudah diterima',
        'done' => 'Selesai',
        'pending_approval' => 'Menunggu Approval',
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
        'pending_approval' => 'bg-blue-100 text-blue-800',
        default => 'bg-gray-200 text-gray-800',
    };
@endphp

<span class="inline-flex items-center px-1.5 py-0.5 rounded text-xs font-medium {{ $psColor }} cursor-pointer" 
      @if($finalBmNotes) data-bmnotes="{{ e($finalBmNotes) }}" @endif
      onclick="openPurchasingStatusModal('{{ $ps }}','{{ $psText }}','{{ $finalRequestId }}'{{ $finalBmNotes ? ', this' : '' }})">
    {{ $psText }}
</span>
