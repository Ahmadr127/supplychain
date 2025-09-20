@extends('layouts.app')

@section('title', 'Edit Approval Request')

@section('content')
<div class="w-full mx-auto max-w-4xl">
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 bg-white border-b border-gray-200">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-900">Edit Approval Request</h2>
                <div class="flex space-x-2">
                    <a href="{{ route('approval-requests.show', $approvalRequest) }}" 
                       class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                        Lihat Detail
                    </a>
                    <a href="{{ route('approval-requests.index') }}" 
                       class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                        Kembali
                    </a>
                </div>
            </div>
        </div>

        <div class="p-6">
            <!-- Request Info -->
            <div class="bg-gray-50 rounded-lg p-6 mb-6">
                <h3 class="text-lg font-semibold text-gray-900 mb-4">Informasi Request</h3>
                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Request Number</label>
                        <p class="mt-1 text-sm text-gray-900">{{ $approvalRequest->request_number }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Workflow</label>
                        <p class="mt-1 text-sm text-gray-900">{{ $approvalRequest->workflow->name }}</p>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700">Status</label>
                        <p class="mt-1">
                            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium
                                {{ $approvalRequest->status == 'pending' ? 'bg-yellow-100 text-yellow-800' : 
                                   ($approvalRequest->status == 'approved' ? 'bg-green-100 text-green-800' : 
                                   ($approvalRequest->status == 'rejected' ? 'bg-red-100 text-red-800' : 'bg-gray-100 text-gray-800')) }}">
                                {{ ucfirst($approvalRequest->status) }}
                            </span>
                        </p>
                    </div>
                </div>
            </div>

            <form action="{{ route('approval-requests.update', $approvalRequest) }}" method="POST">
                @csrf
                @method('PUT')
                
                <div class="grid grid-cols-1 gap-6">
                    <!-- Title -->
                    <div>
                        <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
                            Judul Request <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="title" name="title" value="{{ old('title', $approvalRequest->title) }}" required
                               class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('title') border-red-500 @enderror"
                               placeholder="Masukkan judul request">
                        @error('title')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Description -->
                    <div>
                        <label for="description" class="block text-sm font-medium text-gray-700 mb-2">
                            Deskripsi
                        </label>
                        <textarea id="description" name="description" rows="4"
                                  class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('description') border-red-500 @enderror"
                                  placeholder="Masukkan deskripsi detail request">{{ old('description', $approvalRequest->description) }}</textarea>
                        @error('description')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Current Data -->
                    @if($approvalRequest->data)
                    <div>
                        <label class="block text-sm font-medium text-gray-700 mb-2">Data Request Saat Ini</label>
                        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
                            <pre class="text-sm text-gray-900 whitespace-pre-wrap">{{ json_encode($approvalRequest->data, JSON_PRETTY_PRINT) }}</pre>
                        </div>
                    </div>
                    @endif

                    <!-- Dynamic Data Fields -->
                    <div id="dynamicFields">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Update Data Request</h3>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <p class="text-sm text-gray-600 mb-4">Update atau tambahkan data tambahan untuk request ini</p>
                            
                            <div id="dataFields">
                                @if($approvalRequest->data)
                                    @foreach($approvalRequest->data as $key => $value)
                                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4 p-4 border border-gray-200 rounded-lg">
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Field Name</label>
                                            <input type="text" name="data[{{ $loop->iteration }}][key]" value="{{ $key }}" required
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                   placeholder="Nama field">
                                        </div>
                                        <div>
                                            <label class="block text-sm font-medium text-gray-700 mb-1">Field Value</label>
                                            <input type="text" name="data[{{ $loop->iteration }}][value]" value="{{ is_array($value) ? json_encode($value) : $value }}" required
                                                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                                                   placeholder="Nilai field">
                                        </div>
                                        <div class="flex items-end">
                                            <button type="button" onclick="removeDataField(this)" class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                                                Hapus
                                            </button>
                                        </div>
                                    </div>
                                    @endforeach
                                @endif
                            </div>
                            
                            <button type="button" onclick="addDataField()" class="mt-4 bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                Tambah Field
                            </button>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end space-x-4 mt-8">
                    <a href="{{ route('approval-requests.show', $approvalRequest) }}" 
                       class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-6 rounded">
                        Batal
                    </a>
                    <button type="submit" 
                            class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded">
                        Update Request
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let dataFieldCount = {{ $approvalRequest->data ? count($approvalRequest->data) : 0 }};

function addDataField() {
    dataFieldCount++;
    const container = document.getElementById('dataFields');
    
    const fieldDiv = document.createElement('div');
    fieldDiv.className = 'grid grid-cols-1 md:grid-cols-3 gap-4 mb-4 p-4 border border-gray-200 rounded-lg';
    fieldDiv.innerHTML = `
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Field Name</label>
            <input type="text" name="data[${dataFieldCount}][key]" required
                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                   placeholder="Nama field">
        </div>
        <div>
            <label class="block text-sm font-medium text-gray-700 mb-1">Field Value</label>
            <input type="text" name="data[${dataFieldCount}][value]" required
                   class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500"
                   placeholder="Nilai field">
        </div>
        <div class="flex items-end">
            <button type="button" onclick="removeDataField(this)" class="w-full bg-red-600 hover:bg-red-700 text-white font-bold py-2 px-4 rounded">
                Hapus
            </button>
        </div>
    `;
    
    container.appendChild(fieldDiv);
}

function removeDataField(button) {
    button.closest('.grid').remove();
}
</script>
@endsection
