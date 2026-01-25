{{-- CapEx ID Selector Component --}}
{{-- Used by Manager Unit to select CapEx budget allocation --}}

@props([
    'capexIds' => collect(),
    'selectedId' => null,
    'name' => 'selected_capex_id',
    'required' => false,
    'disabled' => false,
    'fiscalYear' => null,
])

@php
    // Filter by fiscal year if provided
    $filteredCapex = $capexIds;
    if ($fiscalYear) {
        $filteredCapex = $capexIds->where('fiscal_year', $fiscalYear);
    }
    
    // Only show active CapEx with remaining budget
    $availableCapex = $filteredCapex->filter(function($capex) {
        return $capex->is_active && ($capex->getRemainingBudget() > 0);
    });
@endphp

<div class="capex-selector">
    <label class="block text-xs font-medium text-gray-700 mb-1">
        ID Number CapEx
        @if($required)
            <span class="text-red-500">*</span>
        @endif
    </label>
    
    <select 
        name="{{ $name }}" 
        id="{{ $name }}"
        class="block w-full px-3 py-2 text-sm border border-gray-300 rounded-md shadow-sm focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 @if($disabled) bg-gray-100 cursor-not-allowed @endif"
        @if($required) required @endif
        @if($disabled) disabled @endif
    >
        <option value="">-- Pilih CapEx ID --</option>
        
        @forelse($availableCapex as $capex)
            @php
                $remaining = $capex->getRemainingBudget();
                $percentUsed = $capex->budget_amount > 0 
                    ? round(($capex->budget_amount - $remaining) / $capex->budget_amount * 100, 1)
                    : 0;
            @endphp
            <option 
                value="{{ $capex->id }}" 
                @if($selectedId == $capex->id) selected @endif
                data-budget="{{ $capex->budget_amount }}"
                data-remaining="{{ $remaining }}"
            >
                {{ $capex->code }} - {{ Str::limit($capex->description, 30) }}
                (Sisa: Rp {{ number_format($remaining, 0, ',', '.') }} / {{ 100 - $percentUsed }}%)
            </option>
        @empty
            <option value="" disabled>Tidak ada CapEx ID tersedia</option>
        @endforelse
    </select>
    
    {{-- Budget Info Display --}}
    <div id="{{ $name }}_info" class="hidden mt-2 p-2 bg-blue-50 rounded-md border border-blue-200">
        <div class="flex justify-between items-center text-xs">
            <span class="text-gray-600">Total Budget:</span>
            <span id="{{ $name }}_total" class="font-semibold text-gray-900">-</span>
        </div>
        <div class="flex justify-between items-center text-xs mt-1">
            <span class="text-gray-600">Sisa Budget:</span>
            <span id="{{ $name }}_remaining" class="font-semibold text-blue-700">-</span>
        </div>
        <div class="mt-2">
            <div class="w-full bg-gray-200 rounded-full h-2">
                <div id="{{ $name }}_bar" class="bg-blue-600 h-2 rounded-full" style="width: 0%"></div>
            </div>
        </div>
    </div>
    
    @if($errors->has($name))
        <p class="mt-1 text-xs text-red-600">{{ $errors->first($name) }}</p>
    @endif
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const select = document.getElementById('{{ $name }}');
    const infoBox = document.getElementById('{{ $name }}_info');
    const totalSpan = document.getElementById('{{ $name }}_total');
    const remainingSpan = document.getElementById('{{ $name }}_remaining');
    const progressBar = document.getElementById('{{ $name }}_bar');
    
    function updateCapexInfo() {
        const selectedOption = select.options[select.selectedIndex];
        
        if (selectedOption && selectedOption.value) {
            const budget = parseFloat(selectedOption.dataset.budget || 0);
            const remaining = parseFloat(selectedOption.dataset.remaining || 0);
            const used = budget - remaining;
            const percentUsed = budget > 0 ? (used / budget * 100) : 0;
            
            totalSpan.textContent = 'Rp ' + budget.toLocaleString('id-ID');
            remainingSpan.textContent = 'Rp ' + remaining.toLocaleString('id-ID');
            progressBar.style.width = percentUsed + '%';
            
            // Color based on usage
            if (percentUsed > 90) {
                progressBar.className = 'bg-red-600 h-2 rounded-full';
            } else if (percentUsed > 70) {
                progressBar.className = 'bg-yellow-500 h-2 rounded-full';
            } else {
                progressBar.className = 'bg-blue-600 h-2 rounded-full';
            }
            
            infoBox.classList.remove('hidden');
        } else {
            infoBox.classList.add('hidden');
        }
    }
    
    select.addEventListener('change', updateCapexInfo);
    
    // Initialize if already selected
    if (select.value) {
        updateCapexInfo();
    }
});
</script>
