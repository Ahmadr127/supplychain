@extends('layouts.app')

@section('title', 'Detail Approval Request')

@section('content')
<div class="w-full px-0">
    <div class="bg-white overflow-visible shadow-none rounded-none">
        <!-- Header -->
        <div class="p-2 bg-white border-b border-gray-200">
            <div class="flex justify-between items-center">
                <div>
                    <h2 class="text-xl font-bold text-gray-900">{{ $approvalRequest->submissionType->name ?? 'Request' }}</h2>
                    <p class="text-sm text-gray-600">{{ $approvalRequest->request_number }}</p>
                </div>
                <div class="flex space-x-2">
                    @if(($approvalRequest->status == 'pending' || $approvalRequest->status == 'on progress') && $approvalRequest->requester_id == auth()->id())
                        <a href="{{ route('approval-requests.edit', $approvalRequest) }}" 
                           class="bg-blue-600 hover:bg-blue-700 text-white font-medium py-2 px-3 rounded text-sm">
                            Edit
                        </a>
                    @endif
                </div>
            </div>
        </div>

        <div class="p-2">
            <!-- Success/Error Messages (NEW) -->
            @if(session('success'))
            <div class="mb-3 bg-green-50 border border-green-200 rounded-lg p-3 flex items-center justify-between">
                <div class="flex items-center">
                    <i class="fas fa-check-circle text-green-600 mr-2"></i>
                    <span class="text-sm text-green-800">{{ session('success') }}</span>
                </div>
                <button onclick="this.parentElement.remove()" class="text-green-600 hover:text-green-800">
                    <i class="fas fa-times"></i>
                </button>
            </div>
            @endif
            
            @if($errors->any())
            <div class="mb-3 bg-red-50 border border-red-200 rounded-lg p-3">
                <div class="flex items-center justify-between mb-2">
                    <div class="flex items-center">
                        <i class="fas fa-exclamation-circle text-red-600 mr-2"></i>
                        <span class="text-sm font-semibold text-red-800">Terjadi kesalahan:</span>
                    </div>
                    <button onclick="this.parentElement.parentElement.remove()" class="text-red-600 hover:text-red-800">
                        <i class="fas fa-times"></i>
                    </button>
                </div>
                <ul class="list-disc list-inside text-sm text-red-700">
                    @foreach($errors->all() as $error)
                        <li>{{ $error }}</li>
                    @endforeach
                </ul>
            </div>
            @endif
            
            @php
                // Ensure departments map is available even if controller didn't pass it
                if (!isset($departmentsMap)) {
                    $departmentsMap = \App\Models\Department::pluck('name', 'id');
                }
            @endphp
            <div class="grid grid-cols-1 xl:grid-cols-3 gap-3">
                <!-- Main Content -->
                <div class="xl:col-span-2 space-y-4">
                    <!-- Request Info -->
                    <div class="bg-gray-50 rounded-lg p-3">
                        <h3 class="text-base font-semibold text-gray-900 mb-3">Informasi Request</h3>
                        
                        @php
                            $ps = $approvalRequest->purchasing_status ?? 'unprocessed';
                            $psText = match($ps){
                                'unprocessed' => 'Belum diproses',
                                'benchmarking' => 'Pemilihan vendor',
                                'selected' => 'Proses PR & PO',
                                'po_issued' => 'Proses di vendor',
                                'grn_received' => 'Barang sudah diterima',
                                'done' => 'Selesai',
                                default => strtoupper($ps),
                            };
                            $psColor = match($ps){
                                'unprocessed' => 'bg-gray-100 text-gray-700',
                                'benchmarking' => 'bg-yellow-100 text-yellow-800',
                                'selected' => 'bg-blue-100 text-blue-800',
                                'po_issued' => 'bg-indigo-100 text-indigo-800',
                                'grn_received' => 'bg-teal-100 text-teal-800',
                                'done' => 'bg-green-100 text-green-800',
                                default => 'bg-gray-100 text-gray-700',
                            };
                        @endphp
                        
                        <!-- 2 Column Layout -->
                        <div class="grid grid-cols-1 md:grid-cols-2 gap-x-6 gap-y-2 text-xs">
                            <!-- Column 1 -->
                            <div class="space-y-2">
                                <div class="flex items-center gap-2">
                                    <span class="text-gray-600 w-32">Status Purchasing:</span>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium {{ $psColor }}">{{ $psText }}</span>
                                </div>
                                
                                <div class="flex items-center gap-2">
                                    <span class="text-gray-600 w-32">Workflow:</span>
                                    <span class="font-medium text-gray-900">{{ $approvalRequest->workflow->name }}</span>
                                </div>
                                
                                <div class="flex items-center gap-2">
                                    <span class="text-gray-600 w-32">Sifat Pengadaan:</span>
                                    <span class="font-medium text-gray-900">{{ $approvalRequest->procurementType->name ?? '-' }}</span>
                                </div>
                            </div>
                            
                            <!-- Column 2 -->
                            <div class="space-y-2">
                                <div class="flex items-center gap-2">
                                    <span class="text-gray-600 w-20">Dibuat:</span>
                                    <span class="font-medium text-gray-900">{{ $approvalRequest->created_at->format('d/m/Y H:i') }}</span>
                                </div>
                            </div>
                        </div>
                        
                        @if($approvalRequest->description)
                        <div class="mt-3 pt-2 border-t border-gray-200">
                            <div class="flex items-start gap-2">
                                <span class="text-xs text-gray-600 w-32">Deskripsi:</span>
                                <span class="text-xs text-gray-900 flex-1">{{ $approvalRequest->description }}</span>
                            </div>
                        </div>
                        @endif

                        @php
                            $doneWithNotes = ($approvalRequest->purchasingItems ?? collect())
                                ->where('status', 'done')
                                ->filter(fn($pi) => !empty($pi->done_notes));
                            $notesInline = $doneWithNotes->map(function($pi){
                                $name = $pi->masterItem->name ?? 'Item';
                                $note = trim(preg_replace('/\s+/',' ', (string)$pi->done_notes));
                                return $name . ': ' . $note;
                            })->implode(' • ');
                        @endphp
                        @if($doneWithNotes->count())
                        <div class="mt-3 pt-2 border-t border-gray-200">
                            <div class="flex items-start gap-2">
                                <span class="text-xs text-gray-600 font-medium w-40">Catatan Purchasing (DONE):</span>
                                <span class="text-xs text-gray-900 flex-1">{{ $notesInline }}</span>
                            </div>
                        </div>
                        @endif
                    </div>

                    <!-- Items Section -->
                    @if($approvalRequest->items->count() > 0)
                    <div class="bg-gray-50 rounded-lg p-2">
                        <h3 class="text-base font-semibold text-gray-900 mb-3">Item yang Diminta</h3>
                        
                        <!-- Items Cards (Visible) -->
                        <div class="space-y-3">
                            @foreach($approvalRequest->items as $item)
                            @php
                                // Filter by item_id if provided
                                if(isset($filterItemId) && $filterItemId && $item->id != $filterItemId) {
                                    continue;
                                }
                            @endphp
                            @php
                                $masterItem = $item->masterItem; // Access master item via relationship
                                $qty = (int) ($item->quantity ?? 0);
                                $unitPrice = $item->unit_price;
                                $totalPrice = $item->total_price;
                                
                                // Sembunyikan kartu untuk item hasil Form Statis
                                $__hideFormStatis = \Illuminate\Support\Str::contains($masterItem->name ?? '', '(Form Statis)');
                            @endphp
                            @continue($__hideFormStatis)
                            <div class="bg-white border border-gray-200 rounded-lg p-3 shadow-sm">
                                <!-- Item Header -->
                                <div class="flex items-start justify-between mb-3 pb-2 border-b border-gray-200">
                                    <div class="flex-1">
                                        <h4 class="text-sm font-semibold text-gray-900">{{ $masterItem->name }}</h4>
                                    </div>
                                    <x-approval-status-badge :status="$item->status" :requestStatus="$approvalRequest->status" />
                                </div>
                                
                                <!-- Item Details (Horizontal Label-Value, 3 Columns) -->
                                <div class="grid grid-cols-1 md:grid-cols-3 gap-x-4 gap-y-2 text-xs">
                                    <!-- Column 1 -->
                                    <div class="space-y-1.5">
                                        <div class="flex items-center gap-2">
                                            <span class="text-gray-600 w-24">Tipe:</span>
                                            <span class="font-medium text-gray-900">{{ $masterItem->itemType->name ?? '-' }}</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-gray-600 w-24">Kategori:</span>
                                            <span class="font-medium text-gray-900">{{ $masterItem->itemCategory->name ?? '-' }}</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-gray-600 w-24">Jumlah:</span>
                                            <span class="font-medium text-gray-900">{{ $qty }} {{ $masterItem->unit->name ?? '' }}</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-gray-600 w-24">Harga Satuan:</span>
                                            <span class="font-medium text-gray-900">{{ $unitPrice !== null ? 'Rp '.number_format((float)$unitPrice, 0, ',', '.') : '-' }}</span>
                                        </div>
                                    </div>
                                    
                                    <!-- Column 2 -->
                                    <div class="space-y-1.5">
                                        <div class="flex items-center gap-2">
                                            <span class="text-gray-600 w-32 flex-shrink-0">Merk:</span>
                                            <span class="font-medium text-gray-900">{{ $item->brand ?? '-' }}</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-gray-600 w-32 flex-shrink-0">Vendor Alt:</span>
                                            <span class="font-medium text-gray-900 truncate">{{ $item->alternative_vendor ?? '-' }}</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-gray-600 w-32 flex-shrink-0">No Surat:</span>
                                            <span class="font-medium text-gray-900">{{ $item->letter_number ?? '-' }}</span>
                                        </div>
                                        <div class="flex items-center gap-2">
                                            <span class="text-gray-600 w-32 flex-shrink-0">Unit Peruntukan:</span>
                                            @php $allocDeptName = $departmentsMap[$item->allocation_department_id ?? null] ?? '-'; @endphp
                                            <span class="font-medium text-gray-900">{{ $allocDeptName }}</span>
                                        </div>
                                    </div>
                                    
                                    <!-- Column 3 -->
                                    <div class="space-y-1.5">
                                        <div class="flex items-start gap-2">
                                            <span class="text-gray-600 w-20">Spesifikasi:</span>
                                            <span class="font-medium text-gray-900 flex-1">{{ $item->specification ?? '-' }}</span>
                                        </div>
                                        <div class="flex items-start gap-2">
                                            <span class="text-gray-600 w-20">Catatan:</span>
                                            <span class="font-medium text-gray-900 flex-1">{{ $item->notes ?? '-' }}</span>
                                        </div>
                                    </div>
                                </div>
                                
                                <!-- Dokumen Pendukung (Full Width) -->
                                @php
                                    $filesForItem = isset($itemFiles) ? ($itemFiles->get($masterItem->id) ?? collect()) : collect();
                                @endphp
                                @if($filesForItem->count())
                                <div class="mt-3 pt-3 border-t border-gray-200">
                                    <span class="text-xs text-gray-600 font-medium">Dokumen Pendukung:</span>
                                    <div class="mt-1 flex flex-wrap gap-2">
                                        @foreach($filesForItem as $f)
                                            <a href="{{ route('approval-requests.view-attachment', $f->id) }}" target="_blank" 
                                               class="inline-flex items-center px-2 py-1 bg-blue-50 border border-blue-200 rounded text-xs text-blue-700 hover:bg-blue-100">
                                                <i class="fas fa-file-alt mr-1"></i>
                                                {{ $f->original_name }}
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                                @endif
                                
                                @php
                                    $piForItem = ($approvalRequest->purchasingItems ?? collect())->firstWhere('master_item_id', $masterItem->id);
                                @endphp
                                @if($piForItem)
                                <div class="mt-3 pt-3 border-t border-gray-200">
                                    <div class="text-xs font-semibold text-gray-700 mb-2">
                                        <i class="fas fa-shopping-cart mr-1"></i>Informasi Purchasing
                                    </div>
                                    
                                    <div class="space-y-2 bg-gray-50 rounded-md p-2">
                                        <!-- Status Purchasing -->
                                        <div class="flex items-start gap-2">
                                            <span class="text-xs text-gray-600 w-32 flex-shrink-0">Status:</span>
                                            <span class="text-xs font-medium text-gray-900">
                                                @php
                                                    $statusText = match($piForItem->status) {
                                                        'unprocessed' => 'Belum diproses',
                                                        'benchmarking' => 'Pemilihan vendor',
                                                        'selected' => 'Proses PR & PO',
                                                        'po_issued' => 'Proses di vendor',
                                                        'grn_received' => 'Barang diterima',
                                                        'done' => 'Selesai',
                                                        default => ucfirst($piForItem->status),
                                                    };
                                                @endphp
                                                {{ $statusText }}
                                            </span>
                                        </div>
                                        
                                        <!-- Tanggal Dibuat -->
                                        <div class="flex items-start gap-2">
                                            <span class="text-xs text-gray-600 w-32 flex-shrink-0">Tanggal Dibuat:</span>
                                            <span class="text-xs text-gray-900">
                                                {{ $piForItem->created_at ? $piForItem->created_at->format('d/m/Y H:i') : '-' }}
                                            </span>
                                        </div>
                                        
                                        <!-- Tanggal Update Status -->
                                        @if($piForItem->status_changed_at)
                                        <div class="flex items-start gap-2">
                                            <span class="text-xs text-gray-600 w-32 flex-shrink-0">Update Status:</span>
                                            <span class="text-xs text-gray-900">
                                                {{ $piForItem->status_changed_at->format('d/m/Y H:i') }}
                                                @if($piForItem->statusChanger)
                                                    <span class="text-gray-500">• {{ $piForItem->statusChanger->name }}</span>
                                                @endif
                                            </span>
                                        </div>
                                        @endif
                                        
                                        <!-- Catatan Benchmarking -->
                                        @if($piForItem->benchmark_notes)
                                        <div class="flex items-start gap-2">
                                            <span class="text-xs text-gray-600 w-32 flex-shrink-0">Catatan Benchmarking:</span>
                                            <span class="text-xs text-gray-900 flex-1 whitespace-pre-wrap">{{ $piForItem->benchmark_notes }}</span>
                                        </div>
                                        @endif
                                        
                                        <!-- Vendor Terpilih -->
                                        @if($piForItem->preferredVendor)
                                        <div class="flex items-start gap-2">
                                            <span class="text-xs text-gray-600 w-32 flex-shrink-0">Vendor Terpilih:</span>
                                            <span class="text-xs text-gray-900">{{ $piForItem->preferredVendor->name }}</span>
                                        </div>
                                        @endif
                                        
                                        <!-- Harga Vendor -->
                                        @if($piForItem->preferred_unit_price)
                                        <div class="flex items-start gap-2">
                                            <span class="text-xs text-gray-600 w-32 flex-shrink-0">Harga Satuan:</span>
                                            <span class="text-xs text-gray-900">Rp {{ number_format($piForItem->preferred_unit_price, 0, ',', '.') }}</span>
                                        </div>
                                        @endif
                                        
                                        @if($piForItem->preferred_total_price)
                                        <div class="flex items-start gap-2">
                                            <span class="text-xs text-gray-600 w-32 flex-shrink-0">Total Harga:</span>
                                            <span class="text-xs font-semibold text-gray-900">Rp {{ number_format($piForItem->preferred_total_price, 0, ',', '.') }}</span>
                                        </div>
                                        @endif
                                        
                                        <!-- PO Number -->
                                        @if($piForItem->po_number)
                                        <div class="flex items-start gap-2">
                                            <span class="text-xs text-gray-600 w-32 flex-shrink-0">No. PO:</span>
                                            <span class="text-xs text-gray-900">{{ $piForItem->po_number }}</span>
                                        </div>
                                        @endif
                                        
                                        <!-- Invoice Number -->
                                        @if($piForItem->invoice_number)
                                        <div class="flex items-start gap-2">
                                            <span class="text-xs text-gray-600 w-32 flex-shrink-0">No. Invoice:</span>
                                            <span class="text-xs text-gray-900">{{ $piForItem->invoice_number }}</span>
                                        </div>
                                        @endif
                                        
                                        <!-- GRN Date -->
                                        @if($piForItem->grn_date)
                                        <div class="flex items-start gap-2">
                                            <span class="text-xs text-gray-600 w-32 flex-shrink-0">Tanggal GRN:</span>
                                            <span class="text-xs text-gray-900">{{ $piForItem->grn_date->format('d/m/Y') }}</span>
                                        </div>
                                        @endif
                                        
                                        <!-- Procurement Cycle Days -->
                                        @if($piForItem->proc_cycle_days)
                                        <div class="flex items-start gap-2">
                                            <span class="text-xs text-gray-600 w-32 flex-shrink-0">Siklus Procurement:</span>
                                            <span class="text-xs text-gray-900">{{ $piForItem->proc_cycle_days }} hari</span>
                                        </div>
                                        @endif
                                        
                                        <!-- Done Notes -->
                                        @if($piForItem->done_notes)
                                        <div class="flex items-start gap-2">
                                            <span class="text-xs text-gray-600 w-32 flex-shrink-0">Catatan Selesai:</span>
                                            <span class="text-xs text-gray-900 flex-1 whitespace-pre-wrap">{{ $piForItem->done_notes }}</span>
                                        </div>
                                        @endif
                                    </div>
                                </div>
                                @endif
                                
                                <!-- FS Document Section for this item -->
                                @if($item->fs_document ?? false)
                                <div class="mt-2 border-t border-gray-200 pt-2">
                                    <div class="flex items-center justify-between">
                                        <div class="text-xs font-semibold text-gray-700">
                                            <i class="fas fa-file-alt mr-1 text-blue-600"></i>
                                            Dokumen FS Item
                                        </div>
                                        <div class="flex gap-2">
                                            <a href="{{ Storage::url($item->fs_document) }}" 
                                               target="_blank"
                                               class="text-xs text-blue-600 hover:text-blue-800 hover:underline">
                                                <i class="fas fa-eye mr-1"></i>Lihat
                                            </a>
                                            <a href="{{ Storage::url($item->fs_document) }}" 
                                               download
                                               class="text-xs text-gray-600 hover:text-gray-800 hover:underline">
                                                <i class="fas fa-download mr-1"></i>Download
                                            </a>
                                        </div>
                                    </div>
                                </div>
                                @endif
                                
                                <!-- Approval Steps History -->
                                @php
                                    $itemSteps = \App\Models\ApprovalItemStep::where('approval_request_id', $approvalRequest->id)
                                        ->where('master_item_id', $masterItem->id)
                                        ->orderBy('step_number')
                                        ->with('approver')
                                        ->get();
                                    
                                    // Show all steps that have been actioned (approved or rejected)
                                    $actionedSteps = $itemSteps->filter(function($step) {
                                        return $step->status !== 'pending';
                                    });
                                @endphp
                                @if($actionedSteps->count() > 0)
                                <div class="mt-3 pt-3 border-t border-gray-200">
                                    <div class="text-xs font-semibold text-gray-700 mb-2">
                                        <i class="fas fa-comments mr-1"></i>Catatan Approval
                                    </div>
                                    <div class="space-y-2">
                                        @foreach($actionedSteps as $step)
                                            <div class="bg-{{ $step->status == 'rejected' ? 'red' : 'gray' }}-50 border border-{{ $step->status == 'rejected' ? 'red' : 'gray' }}-200 rounded-md p-2">
                                                <div class="flex items-center gap-2 mb-1">
                                                    <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium
                                                        {{ $step->status == 'approved' ? 'bg-green-100 text-green-800' : 'bg-red-100 text-red-800' }}">
                                                        @if($step->status == 'approved')
                                                            <i class="fas fa-check mr-0.5"></i>
                                                        @else
                                                            <i class="fas fa-times mr-0.5"></i>
                                                        @endif
                                                        {{ $step->step_name }}
                                                    </span>
                                                    <span class="text-[10px] text-gray-600">
                                                        {{ $step->approver->name ?? 'Unknown' }}
                                                        @if($step->approved_at)
                                                            • {{ \Carbon\Carbon::parse($step->approved_at)->format('d/m/Y H:i') }}
                                                        @endif
                                                    </span>
                                                </div>
                                                @if($step->rejected_reason)
                                                    <div class="text-xs text-red-800 font-medium mb-1">
                                                        <i class="fas fa-exclamation-circle mr-1"></i>Alasan Reject:
                                                    </div>
                                                    <div class="text-xs text-red-700 pl-4">{{ $step->rejected_reason }}</div>
                                                @endif
                                                @if($step->comments)
                                                    <div class="text-xs {{ $step->status == 'rejected' ? 'text-gray-700' : 'text-gray-600' }} {{ $step->rejected_reason ? 'mt-1' : '' }}">
                                                        <strong>Komentar:</strong> {{ $step->comments }}
                                                    </div>
                                                @else
                                                    @if(!$step->rejected_reason)
                                                        <div class="text-[10px] text-gray-400 italic">
                                                            Tidak ada komentar
                                                        </div>
                                                    @endif
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                                @endif
                                
                                <!-- Per-Item Approval Steps moved to sidebar -->
                                
                                <!-- Form Extra Data Component -->
                                @php
                                    $itemExtra = isset($itemExtras) ? $itemExtras->get($masterItem->id) : null;
                                @endphp
                                <x-item-extra-data :itemExtra="$itemExtra" />
                            </div>
                            @endforeach
                            
                            <!-- Total -->
                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-2">
                                <div class="flex justify-between items-center">
                                    <span class="text-sm font-medium text-gray-900">Total Keseluruhan:</span>
                                    <span class="text-sm font-bold text-blue-900">
                                        Rp {{ number_format($approvalRequest->getTotalItemsPrice(), 0, ',', '.') }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    @endif

                    <!-- Attachments Section removed -->

                </div>

                <!-- Sidebar -->
                <div class="space-y-3">
                    <!-- Requester Info -->
                    <div>
                        <h3 class="text-sm font-semibold text-gray-900 mb-2">Requester</h3>
                        <div class="flex items-center">
                            <div class="flex-shrink-0">
                                <div class="h-6 w-6 rounded-full bg-blue-500 flex items-center justify-center">
                                    <span class="text-white font-medium text-xs">
                                        {{ substr($approvalRequest->requester->name, 0, 2) }}
                                    </span>
                                </div>
                            </div>
                            <div class="ml-2">
                                <p class="text-xs font-medium text-gray-900">{{ $approvalRequest->requester->name }}</p>
                                @php
                                    $primaryDepartment = $approvalRequest->requester->departments()->wherePivot('is_primary', true)->first();
                                    $role = $approvalRequest->requester->role;
                                @endphp
                                <p class="text-xs text-gray-500">
                                    {{ $primaryDepartment ? $primaryDepartment->name : 'No Department' }}
                                    @if($role)
                                        • {{ $role->display_name }}
                                    @endif
                                </p>
                            </div>
                        </div>
                    </div>

                    <!-- Per-Item Approval Actions -->
                    @foreach($approvalRequest->items as $item)
                        @php
                            // Filter by item_id if provided
                            if(isset($filterItemId) && $filterItemId && $item->id != $filterItemId) {
                                continue;
                            }
                            $masterItem = $item->masterItem;
                            $__hideFormStatis = \Illuminate\Support\Str::contains($masterItem->name ?? '', '(Form Statis)');
                        @endphp
                        @if(!$__hideFormStatis)
                            <x-item-workflow-approval :item="$item" :approvalRequest="$approvalRequest" />
                        @endif
                    @endforeach

                    <!-- Request Actions -->
                    @if(($approvalRequest->status == 'pending' || $approvalRequest->status == 'on progress') && $approvalRequest->requester_id == auth()->id())
                    <div class="bg-white border border-gray-200 rounded-lg p-3">
                        <h3 class="text-base font-semibold text-gray-900 mb-2">Request Actions</h3>
                        <form action="{{ route('approval-requests.cancel', $approvalRequest) }}" method="POST">
                            @csrf
                            <button type="submit" 
                                    class="w-full bg-gray-600 hover:bg-gray-700 text-white font-medium py-2 px-3 rounded text-sm"
                                    onclick="return confirm('Yakin ingin membatalkan request ini?')">
                                <i class="fas fa-ban mr-1"></i>Cancel Request
                            </button>
                        </form>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Progress Overview removed: approval now per-item -->
        </div>
    </div>
</div>

<script>
// Request-level approval JavaScript removed.
</script>
@endsection
