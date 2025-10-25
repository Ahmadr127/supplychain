@props([
    'title' => 'Data Table',
    'createRoute' => null,
    'createLabel' => 'Tambah Data',
    'filters' => null,
    'pagination' => null,
    'emptyMessage' => 'Tidak ada data',
    'emptyIcon' => 'fas fa-inbox',
    'emptyActionRoute' => null,
    'emptyActionLabel' => 'Tambah Data Pertama',
    'filtersBorder' => true,
])

<div class="w-full mx-auto responsive-table-container" 
     x-data="{
         sidebarCollapsed: localStorage.getItem('sidebarCollapsed') === '1',
         tableWidth: 'calc(100vw - 20rem)', // Default: 100vw - 20rem (320px for expanded sidebar + padding)
         updateTableWidth() {
             // Get fresh state from localStorage
             this.sidebarCollapsed = localStorage.getItem('sidebarCollapsed') === '1';
             
             if (this.sidebarCollapsed) {
                 this.tableWidth = 'calc(100vw - 7rem)'; // 100vw - 7rem (112px for collapsed sidebar + padding)
             } else {
                 this.tableWidth = 'calc(100vw - 20rem)'; // 100vw - 20rem (320px for expanded sidebar + padding)
             }
             
             // Force reflow to ensure changes are applied
             this.$el.offsetHeight;
         }
     }"
     x-init="
         // Initial setup with delay to ensure DOM is ready
         setTimeout(() => {
             updateTableWidth();
         }, 50);
         
         // Watch for sidebar state changes
         $watch('sidebarCollapsed', () => {
             setTimeout(() => updateTableWidth(), 10);
         });
         
         // Listen for storage changes (when sidebar state changes in parent)
         window.addEventListener('storage', (e) => {
             if (e.key === 'sidebarCollapsed') {
                 setTimeout(() => updateTableWidth(), 10);
             }
         });
         
         // Listen for custom sidebar toggle events from parent
         document.addEventListener('sidebar-toggled', () => {
             setTimeout(() => updateTableWidth(), 10);
         });
         
         // Listen for window resize to recalculate
         window.addEventListener('resize', () => {
             setTimeout(() => updateTableWidth(), 10);
         });
         
         // Periodic sync to ensure consistency (fallback)
         setInterval(() => {
             const currentState = localStorage.getItem('sidebarCollapsed') === '1';
             if (this.sidebarCollapsed !== currentState) {
                 updateTableWidth();
             }
         }, 200);
     "
     :style="{ maxWidth: tableWidth }"
     :class="{
         'sidebar-collapsed': sidebarCollapsed,
         'sidebar-expanded': !sidebarCollapsed
     }">
    
    <!-- Main Container -->
    <div class="bg-white overflow-hidden shadow-sm sm:rounded-lg">
        <!-- Filters Section -->
        @if($filters)
            <div class="p-4 bg-white" @class(['border-b border-gray-200' => $filtersBorder])>
                {{ $filters }}
            </div>
        @endif

        <!-- Table Container with Responsive Width -->
        <div class="overflow-x-auto">
            <div class="min-w-full">
                {{ $slot }}
            </div>
        </div>

        <!-- Pagination Section -->
        @if($pagination)
            <div class="px-4 py-3 border-t border-gray-200 bg-white">
                <div class="flex flex-col sm:flex-row justify-between items-center gap-4">
                    <!-- Show entries info -->
                    <div class="text-sm text-gray-700">
                        <span class="font-medium">Menampilkan</span>
                        <span class="font-semibold text-gray-900">{{ $pagination->firstItem() ?? 0 }}</span>
                        <span class="font-medium">sampai</span>
                        <span class="font-semibold text-gray-900">{{ $pagination->lastItem() ?? 0 }}</span>
                        <span class="font-medium">dari</span>
                        <span class="font-semibold text-gray-900">{{ $pagination->total() }}</span>
                        <span class="font-medium">entri</span>
                    </div>
                    
                    <!-- Custom Pagination -->
                    <div class="flex items-center space-x-1 pagination-container">
                        @if ($pagination->onFirstPage())
                            <span class="px-3 py-2 text-sm text-gray-400 bg-gray-100 border border-gray-300 rounded-l-md cursor-not-allowed">
                                <i class="fas fa-chevron-left"></i>
                            </span>
                        @else
                            <a href="{{ $pagination->previousPageUrl() }}" class="px-3 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded-l-md hover:bg-gray-50 hover:text-gray-900 transition-colors duration-150">
                                <i class="fas fa-chevron-left"></i>
                            </a>
                        @endif

                        @php
                            $currentPage = $pagination->currentPage();
                            $lastPage = $pagination->lastPage();
                            $startPage = max(1, $currentPage - 2);
                            $endPage = min($lastPage, $currentPage + 2);
                            
                            // Adjust range if we're near the beginning or end
                            if ($currentPage <= 3) {
                                $endPage = min(5, $lastPage);
                            }
                            if ($currentPage >= $lastPage - 2) {
                                $startPage = max(1, $lastPage - 4);
                            }
                        @endphp

                        @if ($startPage > 1)
                            <a href="{{ $pagination->url(1) }}" class="px-3 py-2 text-sm text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 hover:text-gray-900 transition-colors duration-150">
                                1
                            </a>
                            @if ($startPage > 2)
                                <span class="px-2 py-2 text-sm text-gray-500 ellipsis">...</span>
                            @endif
                        @endif

                        @for ($page = $startPage; $page <= $endPage; $page++)
                            @if ($page == $currentPage)
                                <span class="px-3 py-2 text-sm font-semibold text-white bg-blue-600 border border-blue-600">
                                    {{ $page }}
                                </span>
                            @else
                                <a href="{{ $pagination->url($page) }}" class="px-3 py-2 text-sm text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 hover:text-gray-900 transition-colors duration-150">
                                    {{ $page }}
                                </a>
                            @endif
                        @endfor

                        @if ($endPage < $lastPage)
                            @if ($endPage < $lastPage - 1)
                                <span class="px-2 py-2 text-sm text-gray-500 ellipsis">...</span>
                            @endif
                            <a href="{{ $pagination->url($lastPage) }}" class="px-3 py-2 text-sm text-gray-700 bg-white border border-gray-300 hover:bg-gray-50 hover:text-gray-900 transition-colors duration-150">
                                {{ $lastPage }}
                            </a>
                        @endif

                        @if ($pagination->hasMorePages())
                            <a href="{{ $pagination->nextPageUrl() }}" class="px-3 py-2 text-sm text-gray-700 bg-white border border-gray-300 rounded-r-md hover:bg-gray-50 hover:text-gray-900 transition-colors duration-150">
                                <i class="fas fa-chevron-right"></i>
                            </a>
                        @else
                            <span class="px-3 py-2 text-sm text-gray-400 bg-gray-100 border border-gray-300 rounded-r-md cursor-not-allowed">
                                <i class="fas fa-chevron-right"></i>
                            </span>
                        @endif
                    </div>
                </div>
            </div>
        @endif

        <!-- Empty State -->
        @if(isset($emptyState) && $emptyState)
            <div class="p-12 text-center">
                <div class="mx-auto h-12 w-12 text-gray-400">
                    <i class="{{ $emptyIcon }} text-4xl"></i>
                </div>
                <h3 class="mt-2 text-sm font-medium text-gray-900">{{ $emptyMessage }}</h3>
                @if($emptyActionRoute)
                    <div class="mt-6">
                        <a href="{{ $emptyActionRoute }}" 
                           class="inline-flex items-center px-4 py-2 border border-transparent shadow-sm text-sm font-medium rounded-md text-white bg-blue-600 hover:bg-blue-700 transition-colors duration-200">
                            <i class="fas fa-plus mr-2"></i>
                            {{ $emptyActionLabel }}
                        </a>
                    </div>
                @endif
            </div>
        @endif
    </div>
</div>

<style>
/* Custom scrollbar for table */
.overflow-x-auto::-webkit-scrollbar {
    height: 8px;
}

.overflow-x-auto::-webkit-scrollbar-track {
    background: #f1f5f9;
    border-radius: 4px;
}

.overflow-x-auto::-webkit-scrollbar-thumb {
    background: #cbd5e1;
    border-radius: 4px;
}

.overflow-x-auto::-webkit-scrollbar-thumb:hover {
    background: #94a3b8;
}

/* Responsive table styling */
.responsive-table {
    min-width: 100%;
    border-collapse: separate;
    border-spacing: 0;
}

.responsive-table thead th {
    position: sticky;
    top: 0;
    z-index: 10;
    background-color: #f9fafb;
    border-bottom: 1px solid #e5e7eb;
    padding: 0.125rem 0.125rem;
    font-weight: 600;
    font-size: 0.6875rem;
    text-transform: uppercase;
    letter-spacing: 0.025em;
}

.responsive-table tbody td {
    padding: 0.125rem 0.125rem;
    vertical-align: top;
    border-bottom: 1px solid #f3f4f6;
    font-size: 0.8125rem;
    line-height: 1.3;
}

.responsive-table tbody tr {
    transition: background-color 0.15s ease-in-out;
}

.responsive-table tbody tr:hover {
    background-color: #f8fafc;
}

/* Column spacing */
.responsive-table th:not(:last-child),
.responsive-table td:not(:last-child) {
    border-right: 1px solid #f3f4f6;
}

/* Remove bottom border from last row */
.responsive-table tbody tr:last-child td {
    border-bottom: none;
}

/* Base responsive table container */
.responsive-table-container {
    width: 100%;
    max-width: 100%;
    box-sizing: border-box;
    transition: all 0.3s ease-in-out;
    margin: 0 auto;
}

/* Sidebar state specific widths */
.responsive-table-container.sidebar-expanded {
    max-width: calc(100vw - 20rem) !important; /* 320px sidebar + padding */
    width: calc(100vw - 20rem) !important;
}

.responsive-table-container.sidebar-collapsed {
    max-width: calc(100vw - 7rem) !important; /* 112px sidebar + padding */
    width: calc(100vw - 7rem) !important;
}

/* Ensure table container fills available space */
.responsive-table-container .overflow-x-auto {
    width: 100%;
    max-width: 100%;
    overflow-x: auto;
}

/* Responsive breakpoints */
@media (max-width: 1024px) {
    .responsive-table-container.sidebar-expanded,
    .responsive-table-container.sidebar-collapsed {
        max-width: calc(100vw - 4rem) !important;
        width: calc(100vw - 4rem) !important;
    }
}

@media (max-width: 768px) {
    .responsive-table-container.sidebar-expanded,
    .responsive-table-container.sidebar-collapsed {
        max-width: calc(100vw - 2.5rem) !important;
        width: calc(100vw - 2.5rem) !important;
    }
}

/* Force container to take full available width */
.responsive-table-container {
    flex: 1;
    min-width: 0;
}

/* Ensure proper width calculation on all screen sizes */
@media (min-width: 1025px) {
    .responsive-table-container.sidebar-expanded {
        max-width: calc(100vw - 20rem) !important;
        width: calc(100vw - 20rem) !important;
    }
    
    .responsive-table-container.sidebar-collapsed {
        max-width: calc(100vw - 7rem) !important;
        width: calc(100vw - 7rem) !important;
    }
}

/* Animation for width changes */
.table-width-transition {
    transition: max-width 0.3s ease-in-out;
}

/* Custom pagination styling - Override Laravel default */
.pagination {
    display: none !important;
}

/* Hide Laravel's default pagination text */
.pagination-info {
    display: none !important;
}

/* Responsive pagination container */
.pagination-container {
    display: flex;
    flex-wrap: wrap;
    gap: 0.25rem;
    align-items: center;
    justify-content: center;
    max-width: 100%;
    overflow-x: auto;
}

/* Pagination button responsive sizing */
.pagination-container a,
.pagination-container span {
    flex-shrink: 0;
    min-width: 2.5rem;
    height: 2.5rem;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    font-size: 0.875rem;
    font-weight: 500;
    border-radius: 0.375rem;
    transition: all 0.15s ease-in-out;
}

/* Mobile responsive pagination */
@media (max-width: 640px) {
    .pagination-container {
        gap: 0.125rem;
    }
    
    .pagination-container a,
    .pagination-container span {
        min-width: 2rem;
        height: 2rem;
        font-size: 0.75rem;
        padding: 0.375rem 0.5rem;
    }
    
    /* Hide ellipsis on very small screens */
    .pagination-container .ellipsis {
        display: none;
    }
}

/* Very small screens - show only essential buttons */
@media (max-width: 480px) {
    .pagination-container {
        justify-content: space-between;
        width: 100%;
    }
    
    .pagination-container a,
    .pagination-container span {
        min-width: 1.75rem;
        height: 1.75rem;
        font-size: 0.75rem;
        padding: 0.25rem 0.375rem;
    }
}
</style>
