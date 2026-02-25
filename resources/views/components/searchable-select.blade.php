@props([
    'name',                  {{-- form input name --}}
    'options',               {{-- Collection/array of objects with 'id' & 'label' keys --}}
    'selected'   => '',      {{-- currently selected id --}}
    'placeholder'=> 'Semua', {{-- label when nothing selected --}}
    'searchPlaceholder' => 'Cari...',
    'width'      => 'w-72', {{-- dropdown panel width --}}
])

@php
    $optionsJson = collect($options)->values()->toJson();
    $selectedLabel = $placeholder;
    if ($selected) {
        $found = collect($options)->firstWhere('id', $selected);
        if ($found) {
            $selectedLabel = is_array($found) ? $found['label'] : $found->label;
        }
    }
@endphp

<div x-data="{
        open: false,
        search: '',
        selectedId: '{{ $selected }}',
        selectedLabel: @js($selectedLabel),
        options: {{ $optionsJson }},
        get filtered() {
            if (!this.search) return this.options;
            const q = this.search.toLowerCase();
            return this.options.filter(o => o.label.toLowerCase().includes(q));
        },
        select(id, label) {
            this.selectedId = id;
            this.selectedLabel = label;
            this.open = false;
            this.search = '';
        }
    }"
    @click.outside="open = false"
    class="relative">

    {{-- Hidden input --}}
    <input type="hidden" name="{{ $name }}" :value="selectedId">

    {{-- Trigger button --}}
    <button type="button" @click="open = !open"
        class="w-full flex items-center justify-between px-3 py-2 text-sm border border-gray-300 rounded-md bg-white hover:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors">
        <span class="truncate text-gray-700" x-text="selectedLabel"></span>
        <i class="fas fa-chevron-down text-gray-400 text-xs ml-2 transition-transform duration-200"
           :class="open ? 'rotate-180' : ''"></i>
    </button>

    {{-- Dropdown Panel --}}
    <div x-show="open"
        x-transition:enter="transition ease-out duration-100"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-75"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
        class="absolute z-50 mt-1 {{ $width }} bg-white border border-gray-200 rounded-lg shadow-lg"
        style="display: none;">

        {{-- Search input --}}
        <div class="p-2 border-b border-gray-100">
            <div class="relative">
                <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                <input type="text" x-model="search"
                    placeholder="{{ $searchPlaceholder }}"
                    x-ref="searchInput"
                    @keydown.escape="open = false"
                    x-effect="if(open) $nextTick(() => $refs.searchInput.focus())"
                    class="w-full pl-7 pr-3 py-1.5 text-sm border border-gray-200 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500">
            </div>
        </div>

        {{-- Option list --}}
        <ul class="max-h-56 overflow-y-auto py-1">
            {{-- Placeholder / empty option --}}
            <li @click="select('', @js($placeholder))"
                class="px-3 py-2 text-sm cursor-pointer hover:bg-blue-50 flex items-center gap-2"
                :class="selectedId === '' ? 'bg-blue-50 text-blue-700 font-medium' : 'text-gray-700'">
                <i class="fas fa-check text-blue-600 text-xs w-3" x-show="selectedId === ''"></i>
                <span :class="selectedId === '' ? '' : 'ml-5'">{{ $placeholder }}</span>
            </li>

            {{-- Filtered options --}}
            <template x-for="opt in filtered" :key="opt.id">
                <li @click="select(opt.id, opt.label)"
                    class="px-3 py-2 text-sm cursor-pointer hover:bg-blue-50 flex items-center gap-2"
                    :class="selectedId == opt.id ? 'bg-blue-50 text-blue-700 font-medium' : 'text-gray-700'">
                    <i class="fas fa-check text-blue-600 text-xs w-3" x-show="selectedId == opt.id"></i>
                    <span :class="selectedId == opt.id ? '' : 'ml-5'" x-text="opt.label"></span>
                </li>
            </template>

            {{-- Empty state --}}
            <li x-show="filtered.length === 0"
                class="px-3 py-3 text-sm text-gray-400 text-center italic">
                Tidak ditemukan
            </li>
        </ul>
    </div>
</div>
