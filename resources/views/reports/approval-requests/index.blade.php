@extends('layouts.app')

@section('title', 'Laporan Pengajuan')

@section('content')
<x-responsive-table 
    title="Laporan Pengajuan"
    :pagination="$paginator"
    :emptyState="$paginator->count() === 0"
    emptyMessage="Belum ada data">

    <x-slot name="filters">
        <form method="GET" class="w-full">
            <div class="space-y-2">
                <!-- Row 1: Search, Date From, Date To, Year, Buttons -->
                <div class="grid grid-cols-1 md:grid-cols-12 gap-2 items-end">
                    <div class="md:col-span-5">
                        <label class="block text-xs font-medium text-gray-600 mb-0.5">Pencarian</label>
                        <input type="text" name="search" value="{{ request('search') }}" placeholder="Cari no input / item / status" class="w-full h-8 px-2 py-1 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium text-gray-600 mb-0.5">Dari</label>
                        <input type="date" name="date_from" value="{{ request('date_from') }}" class="w-full h-8 px-2 py-1 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div class="md:col-span-2">
                        <label class="block text-xs font-medium text-gray-600 mb-0.5">Sampai</label>
                        <input type="date" name="date_to" value="{{ request('date_to') }}" class="w-full h-8 px-2 py-1 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500">
                    </div>
                    <div class="md:col-span-1">
                        <label class="block text-xs font-medium text-gray-600 mb-0.5">Tahun</label>
                        <input type="number" name="year" value="{{ request('year') }}" class="w-full h-8 px-2 py-1 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500" placeholder="YYYY">
                    </div>
                    <div class="md:col-span-2 flex md:justify-end gap-2">
                        <button class="h-8 px-3 bg-indigo-600 text-white rounded-md text-xs whitespace-nowrap">Filter</button>
                        <a href="{{ route('reports.approval-requests') }}" class="h-8 px-3 border border-gray-300 rounded-md text-xs flex items-center whitespace-nowrap">Reset</a>
                    </div>
                </div>

                <!-- Row 2: Jenis, Unit, Kategori, Status -->
                <div class="grid grid-cols-1 md:grid-cols-4 gap-2 items-end">
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-0.5">Jenis</label>
                        <select name="submission_type_id" class="w-full h-8 px-2 py-1 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">Semua</option>
                            @foreach($submissionTypes as $s)
                                <option value="{{ $s->id }}" {{ request('submission_type_id') == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-0.5">Unit Pengaju</label>
                        <select name="department_id" class="w-full h-8 px-2 py-1 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">Semua</option>
                            @foreach($departments as $d)
                                <option value="{{ $d->id }}" {{ request('department_id') == $d->id ? 'selected' : '' }}>{{ $d->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-0.5">Kategori</label>
                        <select name="category_id" class="w-full h-8 px-2 py-1 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">Semua</option>
                            @foreach($categories as $c)
                                <option value="{{ $c->id }}" {{ request('category_id') == $c->id ? 'selected' : '' }}>{{ $c->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-gray-600 mb-0.5">Status/Process</label>
                        <select name="status" class="w-full h-8 px-2 py-1 border border-gray-300 rounded-md text-sm focus:ring-1 focus:ring-indigo-500 focus:border-indigo-500">
                            <option value="">Semua</option>
                            @foreach(['pending'=>'Pending','approved'=>'Approved','rejected'=>'Rejected','cancelled'=>'Cancelled'] as $k=>$v)
                                <option value="{{ $k }}" {{ request('status') === $k ? 'selected' : '' }}>{{ $v }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>

                @if(auth()->user()->hasPermission('manage_purchasing'))
                <div class="flex justify-end">
                    <button type="button" id="open-purchasing-modal" class="h-8 px-3 bg-emerald-600 text-white rounded-md text-xs whitespace-nowrap">Purchasing</button>
                </div>
                @endif
            </div>
        </form>
    </x-slot>

    @php
        $data = $rows;
        $columns = $columns;
    @endphp
    <x-data-table :columns="$columns" :data="$data" :actions="true" />

</x-responsive-table>

@if(auth()->user()->hasPermission('manage_purchasing'))
<!-- Purchasing Modal -->
<div id="purchasing-modal" class="fixed inset-0 bg-black/40 hidden z-50">
    <div class="absolute inset-0 flex items-start md:items-center justify-center p-2 md:p-4">
        <div class="w-full max-w-5xl bg-white rounded-lg shadow-lg overflow-hidden">
            <div class="px-3 py-2 border-b border-gray-200 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-gray-900">Purchasing</h3>
                <button type="button" id="close-purchasing-modal" class="text-gray-500 hover:text-gray-700 text-xl leading-none">×</button>
            </div>
            <div class="p-3 space-y-3">
                <!-- Select Purchasing Item -->
                <div class="bg-gray-50 border border-gray-200 rounded p-3">
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-2 items-end">
                        <div class="md:col-span-2 relative">
                            <label class="block text-xs font-medium text-gray-600 mb-0.5">Cari Purchasing Item</label>
                            <input type="hidden" id="pi-id" />
                            <input type="text" id="pi-name" class="h-8 w-full px-2 border border-gray-300 rounded text-sm" placeholder="Cari no input / nama item..." autocomplete="off" />
                            <div id="pi-suggest" class="absolute left-0 right-0 mt-0.5 bg-white border border-gray-200 rounded-md shadow-lg max-h-56 overflow-auto hidden z-50 text-sm"></div>
                        </div>
                        <div>
                            <button type="button" id="pi-clear" class="h-8 px-3 border border-gray-300 rounded text-xs">Clear</button>
                        </div>
                    </div>
                    <div class="text-xs text-gray-500 mt-1">Pilih item terlebih dahulu. Semua form di bawah akan mengarah ke item terpilih.</div>
                </div>

                <!-- Benchmarking Vendors -->
                <form id="form-benchmarking" method="POST" action="#" class="bg-white border border-gray-200 rounded">
                    @csrf
                    <div class="px-3 py-2 border-b border-gray-200 flex items-center justify-between">
                        <div class="text-sm font-semibold text-gray-900">Vendor Benchmarking</div>
                        <div class="text-xs text-gray-500">Isi minimal 1 vendor, disarankan 3</div>
                    </div>
                    <div class="p-3 space-y-2" id="vendors-wrapper-modal">
                        @for($i=0; $i<3; $i++)
                        <div class="grid grid-cols-1 md:grid-cols-4 gap-2">
                            <div class="relative">
                                <input type="hidden" name="vendors[{{ $i }}][supplier_id]" class="supplier-id" />
                                <input type="text" class="supplier-name h-8 w-full px-2 border border-gray-300 rounded text-sm" placeholder="Cari supplier..." autocomplete="off" />
                                <div class="supplier-suggest absolute left-0 right-0 mt-0.5 bg-white border border-gray-200 rounded-md shadow-lg max-h-56 overflow-auto hidden z-50 text-sm"></div>
                            </div>
                            <input type="text" name="vendors[{{ $i }}][unit_price]" class="h-8 px-2 border border-gray-300 rounded text-sm" placeholder="Unit Price (Rp)" />
                            <input type="text" name="vendors[{{ $i }}][total_price]" class="h-8 px-2 border border-gray-300 rounded text-sm" placeholder="Total Price (Rp)" />
                            <input type="text" name="vendors[{{ $i }}][notes]" class="h-8 px-2 border border-gray-300 rounded text-sm" placeholder="Notes" />
                        </div>
                        @endfor
                        <div>
                            <button class="px-3 py-1.5 bg-blue-600 text-white rounded text-sm">Simpan Benchmarking</button>
                        </div>
                    </div>
                </form>

                <!-- Preferred Vendor -->
                <form id="form-preferred" method="POST" action="#" class="bg-white border border-gray-200 rounded">
                    @csrf
                    <div class="px-3 py-2 border-b border-gray-200">
                        <div class="text-sm font-semibold text-gray-900">Preferred Vendor</div>
                    </div>
                    <div class="p-3 grid grid-cols-1 md:grid-cols-5 gap-2 items-end">
                        <div class="md:col-span-2 relative">
                            <label class="block text-xs text-gray-600 mb-0.5">Vendor</label>
                            <input type="hidden" name="supplier_id" class="preferred-supplier-id" />
                            <input type="text" class="preferred-supplier-name h-8 w-full px-2 border border-gray-300 rounded text-sm" placeholder="Cari vendor..." autocomplete="off" />
                            <div class="preferred-supplier-suggest absolute left-0 right-0 mt-0.5 bg-white border border-gray-200 rounded-md shadow-lg max-h-56 overflow-auto hidden z-50 text-sm"></div>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 mb-0.5">Unit Price</label>
                            <input type="text" name="unit_price" class="w-full h-8 px-2 border border-gray-300 rounded text-sm" placeholder="Rp" />
                        </div>
                        <div>
                            <label class="block text-xs text-gray-600 mb-0.5">Total Price</label>
                            <input type="text" name="total_price" class="w-full h-8 px-2 border border-gray-300 rounded text-sm" placeholder="Rp" />
                        </div>
                        <div>
                            <button class="px-3 py-1.5 bg-blue-600 text-white rounded text-sm">Simpan Preferred</button>
                        </div>
                    </div>
                </form>

                <!-- PO, GRN, Invoice -->
                <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                    <form id="form-po" method="POST" action="#" class="bg-white border border-gray-200 rounded p-3 space-y-2">
                        @csrf
                        <label class="block text-xs text-gray-600 mb-0.5">PO Number</label>
                        <div class="flex gap-2">
                            <input type="text" name="po_number" class="flex-1 h-8 px-2 border border-gray-300 rounded text-sm" placeholder="PO..." />
                            <button class="px-3 py-1.5 bg-blue-600 text-white rounded text-sm">Simpan PO</button>
                        </div>
                    </form>
                    <form id="form-grn" method="POST" action="#" class="bg-white border border-gray-200 rounded p-3 space-y-2">
                        @csrf
                        <label class="block text-xs text-gray-600 mb-0.5">GRN Date</label>
                        <div class="flex gap-2">
                            <input type="date" name="grn_date" class="h-8 px-2 border border-gray-300 rounded text-sm" />
                            <button class="px-3 py-1.5 bg-blue-600 text-white rounded text-sm">Simpan GRN</button>
                        </div>
                    </form>
                    <form id="form-invoice" method="POST" action="#" class="bg-white border border-gray-200 rounded p-3 space-y-2">
                        @csrf
                        <label class="block text-xs text-gray-600 mb-0.5">Invoice Number</label>
                        <div class="flex gap-2">
                            <input type="text" name="invoice_number" class="flex-1 h-8 px-2 border border-gray-300 rounded text-sm" placeholder="INV..." />
                            <button class="px-3 py-1.5 bg-blue-600 text-white rounded text-sm">Simpan Invoice</button>
                        </div>
                    </form>
                </div>

                <!-- Mark Done -->
                <div class="flex justify-end">
                    <form id="form-done" method="POST" action="#">
                        @csrf
                        <button class="px-4 py-2 bg-green-600 text-white rounded text-sm">Mark as DONE</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('purchasing-modal');
        const openBtn = document.getElementById('open-purchasing-modal');
        const closeBtn = document.getElementById('close-purchasing-modal');
        const piId = document.getElementById('pi-id');
        const piName = document.getElementById('pi-name');
        const piBox = document.getElementById('pi-suggest');
        const piClear = document.getElementById('pi-clear');

        const routesTpl = {
            benchmarking: "{{ route('purchasing.items.benchmarking', ['purchasingItem' => '__ID__']) }}",
            preferred: "{{ route('purchasing.items.preferred', ['purchasingItem' => '__ID__']) }}",
            po: "{{ route('purchasing.items.po', ['purchasingItem' => '__ID__']) }}",
            grn: "{{ route('purchasing.items.grn', ['purchasingItem' => '__ID__']) }}",
            done: "{{ route('purchasing.items.done', ['purchasingItem' => '__ID__']) }}",
            invoice: "{{ route('purchasing.items.invoice', ['purchasingItem' => '__ID__']) }}",
            supplierSuggest: "{{ route('api.suppliers.suggest') }}",
            itemSuggest: "{{ route('api.purchasing.items.suggest') }}"
        };

        const setActions = (id) => {
            const set = (form, url) => { if (form) form.setAttribute('action', url); };
            set(document.getElementById('form-benchmarking'), routesTpl.benchmarking.replace('__ID__', id));
            set(document.getElementById('form-preferred'), routesTpl.preferred.replace('__ID__', id));
            set(document.getElementById('form-po'), routesTpl.po.replace('__ID__', id));
            set(document.getElementById('form-grn'), routesTpl.grn.replace('__ID__', id));
            set(document.getElementById('form-invoice'), routesTpl.invoice.replace('__ID__', id));
            set(document.getElementById('form-done'), routesTpl.done.replace('__ID__', id));
        };

        const open = () => { modal.classList.remove('hidden'); };
        const close = () => { modal.classList.add('hidden'); };
        if (openBtn) openBtn.addEventListener('click', open);
        if (closeBtn) closeBtn.addEventListener('click', close);
        modal.addEventListener('click', (e) => { if (e.target === modal) close(); });

        // Expose a global function for table action button
        window.selectPurchasingItem = function(id, label) {
            const piId = document.getElementById('pi-id');
            const piName = document.getElementById('pi-name');
            if (piId && piName) {
                piId.value = id;
                piName.value = label || '';
                setActions(id);
                open();
            }
        };

        // Resolve purchasing item by approval_request_id + master_item_id then open
        window.resolveAndOpen = async function(approvalRequestId, masterItemId, label) {
            try {
                const res = await fetch("{{ route('api.purchasing.items.resolve') }}", {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-Requested-With': 'XMLHttpRequest',
                        'X-CSRF-TOKEN': document.querySelector('meta[name=csrf-token]')?.getAttribute('content') || ''
                    },
                    body: JSON.stringify({ approval_request_id: approvalRequestId, master_item_id: masterItemId })
                });
                const data = await res.json();
                if (data && data.id) {
                    window.selectPurchasingItem(String(data.id), label);
                }
            } catch (e) {
                alert('Gagal memilih item.');
            }
        };

        // Purchasing item suggest
        let timerPi;
        const fetchPi = async (q) => {
            try {
                const url = new URL(routesTpl.itemSuggest, window.location.origin);
                url.searchParams.set('search', q);
                const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                const data = await res.json();
                const list = (data.items || []).slice(0, 10);
                if (!list.length) { piBox.innerHTML = '<div class="px-3 py-2 text-sm text-gray-500">Tidak ada hasil</div>'; piBox.classList.remove('hidden'); return; }
                piBox.innerHTML = list.map(i => `
                    <div class="px-3 py-2 hover:bg-gray-50 cursor-pointer" data-id="${i.id}" data-label="${i.label}">
                        <div class="flex justify-between">
                            <span>${i.label}</span>
                        </div>
                    </div>
                `).join('');
                piBox.classList.remove('hidden');
            } catch (e) { /* ignore */ }
        };
        if (piName) {
            piName.addEventListener('input', function(){
                piId.value = '';
                const q = (this.value || '').trim();
                clearTimeout(timerPi);
                if (q.length < 2) { piBox.classList.add('hidden'); piBox.innerHTML=''; return; }
                timerPi = setTimeout(() => fetchPi(q), 200);
            });
            piBox.addEventListener('click', function(e){
                const target = e.target.closest('[data-id]');
                if (!target) return;
                const id = target.getAttribute('data-id');
                const label = target.getAttribute('data-label');
                piId.value = id; piName.value = label; piBox.classList.add('hidden');
                setActions(id);
            });
        }
        if (piClear) {
            piClear.addEventListener('click', function(){
                piId.value=''; piName.value=''; piBox.classList.add('hidden');
                setActions('__ID__');
            });
        }

        // Prevent submit if no purchasing item selected
        const requireItem = (form) => {
            if (!form) return;
            form.addEventListener('submit', function(e){
                if (!piId.value || (this.getAttribute('action')||'').includes('__ID__') || this.getAttribute('action')==='#'){
                    e.preventDefault();
                    alert('Pilih Purchasing Item terlebih dahulu.');
                }
            });
        };
        requireItem(document.getElementById('form-benchmarking'));
        requireItem(document.getElementById('form-preferred'));
        requireItem(document.getElementById('form-po'));
        requireItem(document.getElementById('form-grn'));
        requireItem(document.getElementById('form-invoice'));
        requireItem(document.getElementById('form-done'));

        // Supplier suggest (reuse logic from purchasing item page)
        const vendorWrapper = document.getElementById('vendors-wrapper-modal');
        const bindSuggest = (row, nameSel, idSel, boxSel) => {
            const nameInput = row.querySelector(nameSel);
            const idInput = row.querySelector(idSel);
            const box = row.querySelector(boxSel);
            if (!nameInput || !idInput || !box) return;
            let timer;
            nameInput.addEventListener('input', function(){
                idInput.value='';
                const q=(this.value||'').trim();
                clearTimeout(timer);
                if (q.length < 2) { box.classList.add('hidden'); box.innerHTML=''; return; }
                timer=setTimeout(async ()=>{
                    try{
                        const url=new URL(routesTpl.supplierSuggest, window.location.origin);
                        url.searchParams.set('search', q);
                        const res=await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                        const data=await res.json();
                        const list=(data.suppliers||[]).slice(0,10);
                        if(!list.length){ box.innerHTML='<div class="px-3 py-2 text-sm text-gray-500">Tidak ada hasil</div>'; box.classList.remove('hidden'); return; }
                        box.innerHTML=list.map(s=>`
                            <div class="px-3 py-2 hover:bg-gray-50 cursor-pointer" data-id="${s.id}" data-name="${s.name}">
                                <div class="flex justify-between">
                                    <span>${s.name}</span>
                                    <span class="text-xs text-gray-500">${s.code||''}</span>
                                </div>
                                <div class="text-xs text-gray-400">${s.email||''}${s.phone? ' • '+s.phone:''}</div>
                            </div>
                        `).join('');
                        box.classList.remove('hidden');
                    }catch(e){/* ignore */}
                },200);
            });
            box.addEventListener('click', function(e){
                const target=e.target.closest('[data-id]'); if(!target) return;
                idInput.value=target.getAttribute('data-id');
                nameInput.value=target.getAttribute('data-name');
                box.classList.add('hidden');
            });
            nameInput.addEventListener('blur', ()=> setTimeout(()=> box.classList.add('hidden'), 200));
        };
        if (vendorWrapper) {
            vendorWrapper.querySelectorAll('.grid').forEach(r=> bindSuggest(r, '.supplier-name', '.supplier-id', '.supplier-suggest'));
        }
        // Preferred vendor suggest
        bindSuggest(document, '.preferred-supplier-name', '.preferred-supplier-id', '.preferred-supplier-suggest');
    });
    </script>
</div>
@endif

@endsection
