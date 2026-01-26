{{-- Release Approval Modal --}}
<div id="releaseApprovalModal" 
    class="fixed inset-0 bg-black bg-opacity-50 hidden z-50 flex items-center justify-center"
    x-data="{ 
        show: false,
        item: null,
        step: null,
        action: 'approve',
        notes: '',
        loading: false
    }"
    x-show="show"
    @release-approve.window="
        item = $event.detail.item;
        step = $event.detail.step;
        action = 'approve';
        notes = '';
        show = true;
    "
    @release-reject.window="
        item = $event.detail.item;
        step = $event.detail.step;
        action = 'reject';
        notes = '';
        show = true;
    "
    @keydown.escape.window="show = false"
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
>
    <div class="bg-white rounded-lg shadow-xl max-w-lg w-full mx-4 overflow-hidden"
        @click.away="show = false"
        x-show="show"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0 scale-95"
        x-transition:enter-end="opacity-100 scale-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100 scale-100"
        x-transition:leave-end="opacity-0 scale-95"
    >
        {{-- Header --}}
        <div class="px-6 py-4 border-b border-gray-200" 
            :class="action === 'approve' ? 'bg-purple-50' : 'bg-red-50'">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center"
                        :class="action === 'approve' ? 'bg-purple-100' : 'bg-red-100'">
                        <i class="fas" 
                            :class="action === 'approve' ? 'fa-rocket text-purple-600' : 'fa-times text-red-600'"></i>
                    </div>
                    <div>
                        <h3 class="text-lg font-semibold"
                            :class="action === 'approve' ? 'text-purple-900' : 'text-red-900'"
                            x-text="action === 'approve' ? 'Approve Release' : 'Reject Release'"></h3>
                        <p class="text-xs" 
                            :class="action === 'approve' ? 'text-purple-600' : 'text-red-600'"
                            x-text="step?.step_name || 'Release Step'"></p>
                    </div>
                </div>
                <button @click="show = false" class="text-gray-400 hover:text-gray-600">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>
        </div>

        {{-- Body --}}
        <div class="px-6 py-4">
            {{-- Item Info --}}
            <div class="mb-4 p-3 bg-gray-50 rounded-lg">
                <h4 class="text-sm font-semibold text-gray-700 mb-2">Item Information</h4>
                <div class="grid grid-cols-2 gap-2 text-xs">
                    <div>
                        <span class="text-gray-500">Item:</span>
                        <span class="font-medium text-gray-900" x-text="item?.name || '-'"></span>
                    </div>
                    <div>
                        <span class="text-gray-500">Quantity:</span>
                        <span class="font-medium text-gray-900" x-text="item?.quantity || '-'"></span>
                    </div>
                </div>
            </div>

            {{-- Step Info --}}
            <div class="mb-4 p-3 rounded-lg"
                :class="action === 'approve' ? 'bg-purple-50' : 'bg-red-50'">
                <h4 class="text-sm font-semibold mb-2"
                    :class="action === 'approve' ? 'text-purple-700' : 'text-red-700'">
                    Release Step
                </h4>
                <div class="text-xs space-y-1">
                    <div class="flex justify-between">
                        <span class="text-gray-600">Step Number:</span>
                        <span class="font-medium" x-text="step?.step_number || '-'"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Step Name:</span>
                        <span class="font-medium" x-text="step?.step_name || '-'"></span>
                    </div>
                    <div class="flex justify-between">
                        <span class="text-gray-600">Phase:</span>
                        <span class="font-medium text-purple-600">Release</span>
                    </div>
                </div>
            </div>

            {{-- Notes/Reason --}}
            <div class="mb-4">
                <label class="block text-sm font-medium text-gray-700 mb-1"
                    x-text="action === 'approve' ? 'Catatan Release (opsional)' : 'Alasan Penolakan (wajib)'"></label>
                <textarea 
                    x-model="notes"
                    rows="3"
                    class="w-full px-3 py-2 text-sm border border-gray-300 rounded-md focus:outline-none focus:ring-2"
                    :class="action === 'approve' ? 'focus:ring-purple-500' : 'focus:ring-red-500'"
                    :placeholder="action === 'approve' ? 'Tambahkan catatan release...' : 'Jelaskan alasan penolakan...'"
                    :required="action !== 'approve'"
                ></textarea>
            </div>

            {{-- Warning for reject --}}
            <template x-if="action === 'reject'">
                <div class="mb-4 p-3 bg-yellow-50 rounded-lg border border-yellow-200">
                    <div class="flex items-start gap-2">
                        <i class="fas fa-exclamation-triangle text-yellow-500 mt-0.5"></i>
                        <div class="text-xs text-yellow-700">
                            <p class="font-semibold">Perhatian!</p>
                            <p>Penolakan akan mengembalikan item ke status sebelumnya. Pastikan alasan penolakan jelas.</p>
                        </div>
                    </div>
                </div>
            </template>
        </div>

        {{-- Footer --}}
        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end gap-3">
            <button @click="show = false" 
                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-md hover:bg-gray-50">
                Batal
            </button>
            <button 
                @click="submitRelease()"
                :disabled="loading || (action === 'reject' && !notes.trim())"
                class="px-4 py-2 text-sm font-medium text-white rounded-md disabled:opacity-50 disabled:cursor-not-allowed"
                :class="action === 'approve' ? 'bg-purple-600 hover:bg-purple-700' : 'bg-red-600 hover:bg-red-700'"
            >
                <span x-show="!loading" x-text="action === 'approve' ? 'Approve Release' : 'Reject'"></span>
                <span x-show="loading" class="flex items-center gap-2">
                    <i class="fas fa-spinner fa-spin"></i> Processing...
                </span>
            </button>
        </div>
    </div>
</div>

<script>
function submitRelease() {
    // This function will be called from AlpineJS
    // The actual form submission is handled via x-on directive
    const modal = Alpine.$data(document.getElementById('releaseApprovalModal'));
    if (modal) {
        modal.loading = true;
        
        // Create and submit form
        const form = document.createElement('form');
        form.method = 'POST';
        form.action = modal.action === 'approve' 
            ? `/approval-requests/${modal.item.request_id}/items/${modal.item.id}/approve`
            : `/approval-requests/${modal.item.request_id}/items/${modal.item.id}/reject`;
        
        // CSRF token
        const csrfToken = document.querySelector('meta[name="csrf-token"]').content;
        const csrfInput = document.createElement('input');
        csrfInput.type = 'hidden';
        csrfInput.name = '_token';
        csrfInput.value = csrfToken;
        form.appendChild(csrfInput);
        
        // Notes
        const notesInput = document.createElement('input');
        notesInput.type = 'hidden';
        notesInput.name = 'notes';
        notesInput.value = modal.notes;
        form.appendChild(notesInput);
        
        document.body.appendChild(form);
        form.submit();
    }
}
</script>
