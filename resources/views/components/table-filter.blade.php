@props([
    'filters' => [],
    'searchPlaceholder' => 'Cari...',
    'showDateRange' => true
])

<div class="bg-white p-4 border-b border-gray-200 shadow-sm">
    <div class="flex flex-col lg:flex-row gap-4">
        <!-- Search Input -->
        <div class="flex-1">
            <label for="search" class="block text-sm font-medium text-gray-700 mb-1">Pencarian</label>
            <div class="relative">
                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                    <i class="fas fa-search text-gray-400"></i>
                </div>
                <input 
                    type="text" 
                    id="search" 
                    name="search" 
                    value="{{ request('search') }}"
                    placeholder="{{ $searchPlaceholder }}"
                    class="block w-full pl-10 pr-3 py-2 border border-gray-300 rounded-md leading-5 bg-white placeholder-gray-500 focus:outline-none focus:placeholder-gray-400 focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
                    x-model="filters.search"
                    @input.debounce.300ms="applyFilters()"
                >
            </div>
        </div>


        <!-- Date Range Filter -->
        @if($showDateRange)
        <div class="lg:w-48">
            <label for="date_from" class="block text-sm font-medium text-gray-700 mb-1">Dari Tanggal</label>
            <input 
                type="date" 
                id="date_from" 
                name="date_from"
                value="{{ request('date_from') }}"
                x-model="filters.dateFrom"
                @change="applyFilters()"
                class="block w-full px-3 py-2 border border-gray-300 rounded-md leading-5 bg-white focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
            >
        </div>

        <div class="lg:w-48">
            <label for="date_to" class="block text-sm font-medium text-gray-700 mb-1">Sampai Tanggal</label>
            <input 
                type="date" 
                id="date_to" 
                name="date_to"
                value="{{ request('date_to') }}"
                x-model="filters.dateTo"
                @change="applyFilters()"
                class="block w-full px-3 py-2 border border-gray-300 rounded-md leading-5 bg-white focus:outline-none focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500 sm:text-sm"
            >
        </div>
        @endif

        <!-- Action Buttons -->
        <div class="flex items-end gap-2">
            <button 
                type="button"
                @click="clearFilters()"
                class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-md font-semibold text-xs text-gray-700 uppercase tracking-widest shadow-sm bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 disabled:opacity-25 transition ease-in-out duration-150"
            >
                <i class="fas fa-times mr-2"></i>
                Reset
            </button>
            
            <button 
                type="button"
                @click="applyFilters()"
                class="inline-flex items-center px-4 py-2 border border-transparent rounded-md font-semibold text-xs text-white uppercase tracking-widest bg-indigo-600 hover:bg-indigo-700 focus:bg-indigo-700 active:bg-indigo-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:ring-offset-2 transition ease-in-out duration-150"
            >
                <i class="fas fa-filter mr-2"></i>
                Filter
            </button>
        </div>
    </div>

    <!-- Active Filters Display -->
    <div x-show="hasActiveFilters()" class="mt-4 pt-4 border-t border-gray-200">
        <div class="flex items-center gap-2 flex-wrap">
            <span class="text-sm font-medium text-gray-700">Filter Aktif:</span>
            <template x-for="(value, key) in getActiveFilters()" :key="key">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-indigo-100 text-indigo-800">
                    <span x-text="getFilterLabel(key, value)"></span>
                    <button 
                        @click="removeFilter(key)"
                        class="ml-1.5 inline-flex items-center justify-center w-4 h-4 rounded-full text-indigo-400 hover:bg-indigo-200 hover:text-indigo-500 focus:outline-none focus:ring-2 focus:ring-indigo-500"
                    >
                        <i class="fas fa-times text-xs"></i>
                    </button>
                </span>
            </template>
        </div>
    </div>
</div>

<script>
document.addEventListener('alpine:init', () => {
    Alpine.data('tableFilter', (initialFilters = {}) => ({
        filters: {
            search: initialFilters.search || '',
            dateFrom: initialFilters.dateFrom || '',
            dateTo: initialFilters.dateTo || '',
            ...initialFilters
        },

        init() {
            // Initialize filters from URL params
            this.filters = {
                search: new URLSearchParams(window.location.search).get('search') || '',
                dateFrom: new URLSearchParams(window.location.search).get('date_from') || '',
                dateTo: new URLSearchParams(window.location.search).get('date_to') || '',
                ...initialFilters
            };
        },

        applyFilters() {
            const params = new URLSearchParams();
            
            if (this.filters.search) params.set('search', this.filters.search);
            if (this.filters.dateFrom) params.set('date_from', this.filters.dateFrom);
            if (this.filters.dateTo) params.set('date_to', this.filters.dateTo);
            
            // Preserve existing pagination
            const currentPage = new URLSearchParams(window.location.search).get('page');
            if (currentPage) params.set('page', currentPage);
            
            const newUrl = window.location.pathname + (params.toString() ? '?' + params.toString() : '');
            window.location.href = newUrl;
        },

        clearFilters() {
            this.filters = {
                search: '',
                dateFrom: '',
                dateTo: '',
                ...initialFilters
            };
            
            // Remove all filter params from URL
            const newUrl = window.location.pathname;
            window.location.href = newUrl;
        },

        removeFilter(key) {
            this.filters[key] = '';
            this.applyFilters();
        },

        hasActiveFilters() {
            return Object.values(this.filters).some(value => value !== '' && value !== null);
        },

        getActiveFilters() {
            const active = {};
            Object.entries(this.filters).forEach(([key, value]) => {
                if (value !== '' && value !== null) {
                    active[key] = value;
                }
            });
            return active;
        },

        getFilterLabel(key, value) {
            const labels = {
                search: `Pencarian: "${value}"`,
                dateFrom: `Dari: ${this.formatDate(value)}`,
                dateTo: `Sampai: ${this.formatDate(value)}`
            };
            return labels[key] || `${key}: ${value}`;
        },

        formatDate(dateString) {
            if (!dateString) return '';
            const date = new Date(dateString);
            return date.toLocaleDateString('id-ID', {
                day: '2-digit',
                month: 'short',
                year: 'numeric'
            });
        }
    }));
});
</script>
