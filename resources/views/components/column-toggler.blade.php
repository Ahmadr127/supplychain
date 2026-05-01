@props([
    'columns',
    'storageKey' => 'report_columns_v1',
])

@php
$defaultVisible = ['no','nama_pengaju','process','jenis','tanggal_pengajuan','detail','keterangan','unit_pengaju'];
$columnsData    = collect($columns)->map(fn($c) => ['field' => $c['field'] ?? '', 'label' => $c['label'] ?? ''])->values()->all();
@endphp

<div x-data="columnToggler({{ Js::from($columnsData) }}, {{ Js::from($defaultVisible) }}, {{ Js::from($storageKey) }})"
     @click.outside="open = false"
     class="relative">

    <button type="button"
            @click="open = !open"
            class="h-9 px-3 inline-flex items-center gap-1.5 text-sm font-medium border border-gray-300 hover:bg-gray-50 rounded-md transition-colors"
            title="Pilih Kolom">
        <i class="fas fa-columns text-gray-500"></i>
        <span class="hidden sm:inline text-gray-700">Kolom</span>
        <span class="ml-0.5 inline-flex items-center justify-center w-4 h-4 rounded-full bg-blue-600 text-white text-[10px] font-bold"
              x-text="visibleCount"></span>
    </button>

    <div x-show="open"
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="absolute right-0 z-50 mt-1 bg-white border border-gray-200 rounded-lg shadow-lg"
         :style="{ minWidth: '320px' }"
         style="display:none;">

        {{-- Header --}}
        <div class="flex items-center justify-between px-3 py-2.5 border-b border-gray-100">
            <span class="text-xs font-semibold text-gray-600 uppercase tracking-wide">Tampilkan Kolom</span>
            <div class="flex items-center gap-2 shrink-0 ml-2">
                <button type="button" @click="selectAll()"
                        class="text-xs font-semibold text-green-600 hover:text-green-800 whitespace-nowrap">Pilih Semua</button>
                <span class="text-gray-300">|</span>
                <button type="button" @click="reset()"
                        class="text-xs font-semibold text-blue-600 hover:text-blue-800 whitespace-nowrap">Reset</button>
                <span class="text-gray-300">|</span>
                <button type="button" @click="open = false"
                        class="text-gray-400 hover:text-gray-600 leading-none">✕</button>
            </div>
        </div>

        {{-- Column list (single column for readability) --}}
        <ul class="py-1 max-h-80 overflow-y-auto">
            <template x-for="col in columns" :key="col.field">
                <li class="px-3 py-1">
                    <label class="flex items-center gap-2 cursor-pointer select-none hover:bg-gray-50 rounded px-1 py-0.5">
                        <input type="checkbox"
                               :checked="state[col.field] !== false"
                               @change="toggle(col.field)"
                               class="rounded border-gray-300 text-blue-600 focus:ring-blue-500 h-3.5 w-3.5 shrink-0">
                        <span class="text-sm text-gray-700" x-text="col.label"></span>
                    </label>
                </li>
            </template>
        </ul>

        {{-- Footer count --}}
        <div class="px-3 py-1.5 border-t border-gray-100 text-center">
            <span class="text-xs text-gray-400">
                <span x-text="visibleCount"></span> dari <span x-text="columns.length"></span> kolom ditampilkan
            </span>
        </div>
    </div>
</div>

@once
@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('columnToggler', (columns, defaultVisible, storageKey) => ({
        open: false,
        columns,
        defaultVisible,
        storageKey,
        state: {},

        init() {
            const saved = localStorage.getItem(this.storageKey);
            if (saved) {
                try { this.state = JSON.parse(saved); } catch(e) { this.state = {}; }
            }
            this.columns.forEach(col => {
                if (!(col.field in this.state)) {
                    this.state[col.field] = this.defaultVisible.includes(col.field);
                }
            });
            this.$nextTick(() => this.applyVisibility());
        },

        toggle(field) {
            this.state[field] = !this.state[field];
            this.save();
            this.applyVisibility();
        },

        selectAll() {
            this.columns.forEach(col => { this.state[col.field] = true; });
            this.save();
            this.applyVisibility();
        },

        reset() {
            this.state = {};
            this.columns.forEach(col => {
                this.state[col.field] = this.defaultVisible.includes(col.field);
            });
            this.save();
            this.applyVisibility();
        },

        save() {
            localStorage.setItem(this.storageKey, JSON.stringify(this.state));
        },

        applyVisibility() {
            this.columns.forEach(col => {
                const visible = this.state[col.field] !== false;
                document.querySelectorAll('[data-col="' + col.field + '"]').forEach(el => {
                    el.style.display = visible ? '' : 'none';
                });
            });
        },

        get visibleCount() {
            return this.columns.filter(c => this.state[c.field] !== false).length;
        }
    }));
});
</script>
@endpush
@endonce
