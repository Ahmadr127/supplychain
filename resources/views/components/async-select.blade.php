@props([
    'name',
    'placeholder'   => 'Semua',
    'endpoint',
    'value'         => '',
    'selectedLabel' => '',
    'height'        => 'h-9',
])

<div x-data="asyncSelect({{ Js::from($endpoint) }}, {{ Js::from($value ?: '') }}, {{ Js::from($selectedLabel ?: $placeholder) }}, {{ Js::from($placeholder) }})"
     @click.outside="open = false"
     class="relative">

    <input type="hidden" name="{{ $name }}" :value="selectedId">

    <button type="button"
            @click="openDropdown()"
            class="{{ $height }} w-full flex items-center justify-between px-3 text-sm border border-gray-300 rounded-md bg-white hover:border-blue-400 focus:outline-none focus:ring-2 focus:ring-blue-500 transition-colors">
        <span class="truncate text-gray-700" x-text="selectedLabel"></span>
        <i class="fas fa-chevron-down text-gray-400 text-xs ml-2 transition-transform duration-200"
           :class="open ? 'rotate-180' : ''"></i>
    </button>

    <div x-show="open"
         x-transition:enter="transition ease-out duration-100"
         x-transition:enter-start="opacity-0 scale-95"
         x-transition:enter-end="opacity-100 scale-100"
         x-transition:leave="transition ease-in duration-75"
         x-transition:leave-start="opacity-100 scale-100"
         x-transition:leave-end="opacity-0 scale-95"
         class="absolute z-50 mt-1 min-w-[200px] w-full bg-white border border-gray-200 rounded-lg shadow-lg"
         style="display:none;">

        <div class="p-2 border-b border-gray-100">
            <div class="relative">
                <i class="fas fa-search absolute left-2.5 top-1/2 -translate-y-1/2 text-gray-400 text-xs"></i>
                <input type="text"
                       x-ref="searchInput"
                       x-model="search"
                       @input="onSearch()"
                       @keydown.escape="open = false"
                       placeholder="Cari..."
                       class="w-full pl-7 pr-3 py-1.5 text-sm border border-gray-200 rounded-md focus:outline-none focus:ring-1 focus:ring-blue-500">
            </div>
        </div>

        <ul class="max-h-56 overflow-y-auto py-1">
            <li @click="clear()"
                class="px-3 py-2 text-sm cursor-pointer hover:bg-blue-50 flex items-center gap-2"
                :class="selectedId === '' ? 'bg-blue-50 text-blue-700 font-medium' : 'text-gray-700'">
                <i class="fas fa-check text-blue-600 text-xs w-3" x-show="selectedId === ''"></i>
                <span :class="selectedId === '' ? '' : 'ml-5'" x-text="placeholder"></span>
            </li>

            <li x-show="loading" class="px-3 py-3 text-sm text-gray-400 text-center">
                <i class="fas fa-spinner fa-spin mr-1"></i> Memuat...
            </li>

            <template x-if="!loading">
                <template x-for="item in items" :key="item.id">
                    <li @click="select(item.id, item.name)"
                        class="px-3 py-2 text-sm cursor-pointer hover:bg-blue-50 flex items-center gap-2"
                        :class="selectedId == item.id ? 'bg-blue-50 text-blue-700 font-medium' : 'text-gray-700'">
                        <i class="fas fa-check text-blue-600 text-xs w-3" x-show="selectedId == item.id"></i>
                        <span :class="selectedId == item.id ? '' : 'ml-5'" x-text="item.name"></span>
                    </li>
                </template>
            </template>

            <li x-show="!loading && loaded && items.length === 0"
                class="px-3 py-3 text-sm text-gray-400 text-center italic">
                Tidak ditemukan
            </li>
        </ul>
    </div>
</div>

@once
@push('scripts')
<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('asyncSelect', (endpoint, initialId, initialLabel, placeholder) => ({
        open: false,
        search: '',
        items: [],
        loading: false,
        loaded: false,
        selectedId: initialId,
        selectedLabel: initialLabel || placeholder,
        placeholder,
        endpoint,
        timer: null,

        async fetchItems() {
            this.loading = true;
            try {
                const url = new URL(this.endpoint, window.location.origin);
                if (this.search) url.searchParams.set('q', this.search);
                const res = await fetch(url.toString(), {
                    headers: { 'X-Requested-With': 'XMLHttpRequest' }
                });
                this.items = await res.json();
                this.loaded = true;
            } catch(e) {
                this.items = [];
            } finally {
                this.loading = false;
            }
        },

        openDropdown() {
            this.open = true;
            if (!this.loaded) this.fetchItems();
            this.$nextTick(() => this.$refs.searchInput && this.$refs.searchInput.focus());
        },

        onSearch() {
            clearTimeout(this.timer);
            this.timer = setTimeout(() => this.fetchItems(), 280);
        },

        select(id, label) {
            this.selectedId = id;
            this.selectedLabel = label;
            this.open = false;
            this.search = '';
        },

        clear() {
            this.selectedId = '';
            this.selectedLabel = this.placeholder;
            this.open = false;
            this.search = '';
        }
    }));
});
</script>
@endpush
@endonce
