@extends('layouts.app')

@section('title', 'Buat Approval Request')

@section('content')
<div class="w-full mx-auto max-w-4xl">
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <div class="p-6 bg-white border-b border-gray-200">
            <div class="flex justify-between items-center mb-6">
                <h2 class="text-2xl font-bold text-gray-900">Buat Approval Request</h2>
                <a href="{{ route('approval-requests.index') }}" class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-4 rounded">
                    Kembali
                </a>
            </div>
        </div>

        <div class="p-6">
            <form action="{{ route('approval-requests.store') }}" method="POST">
                @csrf
                
                <div class="grid grid-cols-1 gap-6">
                    <!-- Workflow Selection -->
                    <div>
                        <label for="workflow_id" class="block text-sm font-medium text-gray-700 mb-2">
                            Pilih Workflow <span class="text-red-500">*</span>
                        </label>
                        <select id="workflow_id" name="workflow_id" required
                                class="w-full px-3 py-2 border border-gray-300 rounded-md focus:outline-none focus:ring-2 focus:ring-blue-500 @error('workflow_id') border-red-500 @enderror"
                                onchange="loadWorkflowSteps(this.value)">
                            <option value="">Pilih Workflow</option>
                            @foreach($workflows as $workflow)
                                <option value="{{ $workflow->id }}" {{ old('workflow_id') == $workflow->id ? 'selected' : '' }}>
                                    {{ $workflow->name }} ({{ $workflow->type }})
                                </option>
                            @endforeach
                        </select>
                        @error('workflow_id')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Workflow Steps Preview -->
                    <div id="workflowStepsPreview" class="hidden">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Workflow Steps</h3>
                        <div id="stepsList" class="space-y-2">
                            <!-- Steps will be loaded here -->
                        </div>
                    </div>

                    <!-- Title -->
                    <div>
                        <label for="title" class="block text-sm font-medium text-gray-700 mb-2">
                            Judul Request <span class="text-red-500">*</span>
                        </label>
                        <input type="text" id="title" name="title" value="{{ old('title') }}" required
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
                                  placeholder="Masukkan deskripsi detail request">{{ old('description') }}</textarea>
                        @error('description')
                            <p class="mt-1 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                    </div>

                    <!-- Dynamic Data Fields -->
                    <div id="dynamicFields">
                        <h3 class="text-lg font-semibold text-gray-900 mb-4">Data Request</h3>
                        <div class="bg-gray-50 rounded-lg p-4">
                            <p class="text-sm text-gray-600 mb-4">Tambahkan data tambahan untuk request ini (opsional)</p>
                            
                            <div id="dataFields">
                                <!-- Dynamic fields will be added here -->
                            </div>
                            
                            <button type="button" onclick="addDataField()" class="mt-4 bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-4 rounded">
                                Tambah Field
                            </button>
                        </div>
                    </div>
                </div>

                <div class="flex justify-end space-x-4 mt-8">
                    <a href="{{ route('approval-requests.index') }}" 
                       class="bg-gray-600 hover:bg-gray-700 text-white font-bold py-2 px-6 rounded">
                        Batal
                    </a>
                    <button type="submit" 
                            class="bg-blue-600 hover:bg-blue-700 text-white font-bold py-2 px-6 rounded">
                        Buat Request
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let dataFieldCount = 0;

function loadWorkflowSteps(workflowId) {
    if (!workflowId) {
        document.getElementById('workflowStepsPreview').classList.add('hidden');
        return;
    }

    // Get workflow data from the select option
    const select = document.getElementById('workflow_id');
    const selectedOption = select.options[select.selectedIndex];
    const workflowName = selectedOption.text.split(' (')[0];
    
    // Show loading state
    const stepsList = document.getElementById('stepsList');
    stepsList.innerHTML = `
        <div class="bg-gray-50 border border-gray-200 rounded-lg p-4">
            <div class="flex items-center">
                <div class="animate-spin rounded-full h-4 w-4 border-b-2 border-blue-600 mr-2"></div>
                <p class="text-sm text-gray-600">Memuat workflow steps...</p>
            </div>
        </div>
    `;
    
    document.getElementById('workflowStepsPreview').classList.remove('hidden');
    
    // Fetch workflow steps via AJAX
    fetch(`/api/workflows/${workflowId}/steps`)
        .then(response => response.json())
        .then(data => {
            if (data.success && data.steps) {
                let stepsHtml = '';
                data.steps.forEach((step, index) => {
                    stepsHtml += `
                        <div class="flex items-center p-3 border border-gray-200 rounded-lg bg-white">
                            <div class="flex-shrink-0">
                                <div class="h-8 w-8 rounded-full bg-blue-500 flex items-center justify-center">
                                    <span class="text-white font-medium text-sm">${index + 1}</span>
                                </div>
                            </div>
                            <div class="ml-4 flex-1">
                                <p class="text-sm font-medium text-gray-900">${step.name}</p>
                                <p class="text-xs text-gray-500">${step.approver_type.replace('_', ' ').toUpperCase()}</p>
                            </div>
                        </div>
                    `;
                });
                
                stepsList.innerHTML = stepsHtml;
            } else {
                stepsList.innerHTML = `
                    <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                        <p class="text-sm text-yellow-800">Tidak dapat memuat workflow steps.</p>
                        <p class="text-xs text-yellow-600 mt-1">Request akan melalui tahap approval sesuai dengan workflow yang dipilih.</p>
                    </div>
                `;
            }
        })
        .catch(error => {
            console.error('Error loading workflow steps:', error);
            stepsList.innerHTML = `
                <div class="bg-red-50 border border-red-200 rounded-lg p-4">
                    <p class="text-sm text-red-800">Error memuat workflow steps.</p>
                    <p class="text-xs text-red-600 mt-1">Request akan melalui tahap approval sesuai dengan workflow yang dipilih.</p>
                </div>
            `;
        });
}

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

// Load workflow steps if workflow is already selected
document.addEventListener('DOMContentLoaded', function() {
    const workflowId = document.getElementById('workflow_id').value;
    if (workflowId) {
        loadWorkflowSteps(workflowId);
    }
});
</script>
@endsection
