<script>
document.addEventListener('DOMContentLoaded', function() {
    const wrapper = document.getElementById('vendors-wrapper');

    // Currency helpers
    const parseRupiah = (val) => {
        if (val == null) return 0;
        // If it's already a number, return it as-is
        if (typeof val === 'number') return Math.floor(val);
        // Remove all non-digit characters
        const s = String(val).replace(/[^0-9]/g,'');
        return s ? parseInt(s, 10) : 0;
    };
    const formatRupiah = (n) => {
        // Ensure we're working with a number
        const num = typeof n === 'number' ? n : parseRupiah(n);
        try { return 'Rp ' + (num||0).toLocaleString('id-ID'); } catch { return 'Rp ' + num; }
    };

    const bindRow = (row) => {
        const nameInput = row.querySelector('.supplier-name');
        const idInput = row.querySelector('.supplier-id');
        const box = row.querySelector('.supplier-suggest');
        if (!nameInput || !idInput || !box) return;

        let timer;
        nameInput.addEventListener('input', function() {
            idInput.value = '';
            const q = (this.value || '').trim();
            clearTimeout(timer);
            if (q.length < 2) { box.classList.add('hidden'); box.innerHTML=''; return; }
            timer = setTimeout(async () => {
                try {
                    const url = new URL("{{ route('api.suppliers.suggest') }}", window.location.origin);
                    url.searchParams.set('search', q);
                    const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
                    const data = await res.json();
                    const list = (data.suppliers || []).slice(0, 10);
                    if (!list.length) { box.innerHTML = '<div class="px-3 py-2 text-sm text-gray-500">Tidak ada hasil</div>'; box.classList.remove('hidden'); return; }
                    box.innerHTML = list.map(s => `
                        <div class="px-3 py-2 hover:bg-gray-50 cursor-pointer" data-id="${s.id}" data-name="${s.name}">
                            <div class="flex justify-between">
                                <span>${s.name}</span>
                                <span class="text-xs text-gray-500">${s.code || ''}</span>
                            </div>
                            <div class="text-xs text-gray-400">${s.email || ''}${s.phone? ' • '+s.phone:''}</div>
                        </div>
                    `).join('');
                    box.classList.remove('hidden');
                } catch (e) { /* ignore */ }
            }, 200);
        });

        box.addEventListener('click', function(e) {
            const target = e.target.closest('[data-id]');
            if (!target) return;
            idInput.value = target.getAttribute('data-id');
            nameInput.value = target.getAttribute('data-name');
            box.classList.add('hidden');
        });

        nameInput.addEventListener('blur', () => setTimeout(() => box.classList.add('hidden'), 200));
    };

    const bindCurrencyRow = (row, qty) => {
        const unit = row.querySelector('input[name$="[unit_price]"]');
        const total = row.querySelector('input[name$="[total_price]"]');
        if (!unit || !total) return;
        
        let isFormatting = false;
        
        const onUnitInput = () => {
            if (isFormatting) return;
            isFormatting = true;
            
            const unitVal = parseRupiah(unit.value);
            unit.value = formatRupiah(unitVal);
            
            // Auto-calculate total if not manually set
            if (!total.dataset.manual || total.dataset.manual === 'false') {
                const t = unitVal * Math.max(1, qty);
                total.value = formatRupiah(t);
            }
            
            isFormatting = false;
        };
        
        const onTotalInput = () => {
            if (isFormatting) return;
            isFormatting = true;
            
            const tVal = parseRupiah(total.value);
            total.value = formatRupiah(tVal);
            total.dataset.manual = 'true';
            
            isFormatting = false;
        };
        
        // Clear manual flag when unit price changes
        unit.addEventListener('input', () => {
            total.dataset.manual = 'false';
            onUnitInput();
        });
        unit.addEventListener('blur', onUnitInput);
        
        total.addEventListener('input', onTotalInput);
        total.addEventListener('blur', onTotalInput);
    };

    if (wrapper) {
        const qty = {{ (int) ($item->quantity ?? 1) }};
        wrapper.querySelectorAll('.grid').forEach(r => {
            bindRow(r);
            bindCurrencyRow(r, qty);
            // Format initial values if prefilled
            const unit = r.querySelector('input[name$="[unit_price]"]');
            const total = r.querySelector('input[name$="[total_price]"]');
            if (unit && unit.value) {
                // Check if value is already formatted (contains "Rp")
                if (!String(unit.value).includes('Rp')) {
                    const uv = parseRupiah(unit.value);
                    unit.value = formatRupiah(uv);
                }
            }
            if (total && total.value) {
                // Check if value is already formatted (contains "Rp")
                if (!String(total.value).includes('Rp')) {
                    const tv = parseRupiah(total.value);
                    total.value = formatRupiah(tv);
                    total.dataset.manual = 'true';
                }
            } else if (unit && unit.value && total && !total.value) {
                // Only auto-calculate if total is empty
                const uv = parseRupiah(unit.value);
                total.value = formatRupiah(uv * Math.max(1, qty));
                total.dataset.manual = 'false';
            }
        });
    }

    // Preferred vendor suggest limited to benchmark vendors
    const preferredForm = document.getElementById('preferred-form');
    if (preferredForm) {
        const pName = preferredForm.querySelector('.preferred-supplier-name');
        const pId = preferredForm.querySelector('.preferred-supplier-id');
        const pBox = preferredForm.querySelector('.preferred-supplier-suggest');
        @php
            $benchVendorsData = $item->vendors->map(function($v){
                return [
                    'id' => $v->supplier_id,
                    'name' => optional($v->supplier)->name,
                    'unit_price' => intval($v->unit_price),
                    'total_price' => intval($v->total_price),
                ];
            })->values();
        @endphp
        const benchVendors = @json($benchVendorsData);
        if (pName && pId && pBox) {
            pName.addEventListener('input', function(){
                const q = (this.value||'').toLowerCase().trim();
                pId.value = '';
                const list = benchVendors.filter(v => v.name.toLowerCase().includes(q)).slice(0,10);
                if (!q || list.length === 0) { pBox.innerHTML = '<div class="px-3 py-2 text-sm text-gray-500">Ketik untuk mencari vendor hasil benchmarking</div>'; pBox.classList.remove('hidden'); return; }
                pBox.innerHTML = list.map(v => `<div class="px-3 py-2 hover:bg-gray-50 cursor-pointer" data-id="${v.id}" data-name="${v.name}">${v.name}</div>`).join('');
                pBox.classList.remove('hidden');
            });
            pBox.addEventListener('click', function(e){
                const t = e.target.closest('[data-id]'); if (!t) return;
                pId.value = t.getAttribute('data-id');
                pName.value = t.getAttribute('data-name');
                pBox.classList.add('hidden');
                // Auto fill preferred prices from benchmarking if available
                const found = benchVendors.find(v => String(v.id) === String(pId.value));
                const unitPref = preferredForm.querySelector('input[name="unit_price"]');
                const totalPref = preferredForm.querySelector('input[name="total_price"]');
                if (found && unitPref && totalPref) {
                    // Values are already integers from PHP, use them directly
                    const uv = found.unit_price;
                    const tv = found.total_price;
                    if (uv) {
                        unitPref.value = formatRupiah(uv);
                        // Reset manual flag to allow auto-calculation
                        totalPref.dataset.manual = 'false';
                    }
                    if (tv) { 
                        totalPref.value = formatRupiah(tv); 
                        totalPref.dataset.manual = 'true'; 
                    }
                }
            });
            pName.addEventListener('blur', () => setTimeout(()=> pBox.classList.add('hidden'), 200));
        }

        // Currency formatting and auto total for preferred
        const unitPref = preferredForm.querySelector('input[name="unit_price"]');
        const totalPref = preferredForm.querySelector('input[name="total_price"]');
        const qty = {{ (int) ($item->quantity ?? 1) }};
        
        let isPrefFormatting = false;
        
        const onPrefUnit = () => {
            if (isPrefFormatting) return;
            isPrefFormatting = true;
            
            const uv = parseRupiah(unitPref.value);
            unitPref.value = formatRupiah(uv);
            
            // Auto-calculate total if not manually set
            if (!totalPref.dataset.manual || totalPref.dataset.manual === 'false') {
                const t = uv * Math.max(1, qty);
                totalPref.value = formatRupiah(t);
            }
            
            isPrefFormatting = false;
        };
        
        const onPrefTotal = () => {
            if (isPrefFormatting) return;
            isPrefFormatting = true;
            
            const tv = parseRupiah(totalPref.value);
            totalPref.value = formatRupiah(tv);
            totalPref.dataset.manual = 'true';
            
            isPrefFormatting = false;
        };
        
        if (unitPref && totalPref) {
            // Clear manual flag when unit price changes
            unitPref.addEventListener('input', () => {
                totalPref.dataset.manual = 'false';
                onPrefUnit();
            });
            unitPref.addEventListener('blur', onPrefUnit);
            
            totalPref.addEventListener('input', onPrefTotal);
            totalPref.addEventListener('blur', onPrefTotal);
            
            // Format initial values only if not already formatted
            if (unitPref.value && !String(unitPref.value).includes('Rp')) {
                const uv = parseRupiah(unitPref.value);
                unitPref.value = formatRupiah(uv);
            }
            if (totalPref.value && !String(totalPref.value).includes('Rp')) {
                const tv = parseRupiah(totalPref.value);
                totalPref.value = formatRupiah(tv);
                totalPref.dataset.manual = 'true';
            }
        }

        // Sanitize currency before submit (preferred form)
        preferredForm.addEventListener('submit', async function(e){
            e.preventDefault();
            
            const pNameInput = preferredForm.querySelector('.preferred-supplier-name');
            const pIdInput = preferredForm.querySelector('.preferred-supplier-id');
            const name = (pNameInput.value || '').trim();
            
            // If name exists but no ID selected from benchmarking, show error
            if (name && !pIdInput.value) {
                alert('Silakan pilih vendor dari hasil benchmarking yang tersedia.');
                return;
            }
            
            // Sanitize currency
            const u = preferredForm.querySelector('input[name="unit_price"]');
            const t = preferredForm.querySelector('input[name="total_price"]');
            if (u && u.value) u.value = String(parseRupiah(u.value));
            if (t && t.value) t.value = String(parseRupiah(t.value));
            
            // Submit the form
            preferredForm.submit();
        });
    }
    // Sanitize currency before submit (benchmarking form)
    const bmForm = document.getElementById('benchmarking-form');
    if (bmForm) {
        bmForm.addEventListener('submit', async function(e){
            e.preventDefault();
            
            // Show loading state
            const btn = document.getElementById('btn-save-benchmarking');
            const btnText = btn.querySelector('.btn-text');
            const btnLoading = btn.querySelector('.btn-loading');
            btn.disabled = true;
            btnText.classList.add('hidden');
            btnLoading.classList.remove('hidden');
            
            // First, resolve all vendor names to IDs
            const rows = bmForm.querySelectorAll('#vendors-wrapper .grid');
            let hasError = false;
            
            for (const row of rows) {
                const nameInput = row.querySelector('.supplier-name');
                const idInput = row.querySelector('.supplier-id');
                const unitInput = row.querySelector('input[name$="[unit_price]"]');
                const totalInput = row.querySelector('input[name$="[total_price]"]');
                
                // Skip empty rows
                const name = (nameInput.value || '').trim();
                const hasPrice = (unitInput.value || '').trim() || (totalInput.value || '').trim();
                
                if (!name && !hasPrice) continue;
                
                // If name exists but no ID, try to resolve/create supplier
                if (name && !idInput.value) {
                    try {
                        const resolveUrl = new URL("{{ route('api.suppliers.resolve') }}", window.location.origin);
                        const res = await fetch(resolveUrl, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content
                            },
                            body: JSON.stringify({ name: name })
                        });
                        
                        if (res.ok) {
                            const data = await res.json();
                            if (data.success && data.supplier) {
                                idInput.value = data.supplier.id;
                                nameInput.value = data.supplier.name;
                            }
                        } else {
                            alert('Gagal membuat/mencari vendor: ' + name);
                            hasError = true;
                            break;
                        }
                    } catch (err) {
                        console.error('Error resolving supplier:', err);
                        alert('Error saat memproses vendor: ' + name);
                        hasError = true;
                        break;
                    }
                }
            }
            
            if (hasError) {
                // Reset button state on error
                btn.disabled = false;
                btnText.classList.remove('hidden');
                btnLoading.classList.add('hidden');
                return;
            }
            
            // Sanitize currency values - convert formatted values back to plain numbers
            const inputs = bmForm.querySelectorAll('input[name$="[unit_price]"], input[name$="[total_price]"]');
            inputs.forEach(inp => { 
                if (inp.value) {
                    const numValue = parseRupiah(inp.value);
                    inp.value = String(numValue);
                }
            });
            
            // Now submit the form
            bmForm.submit();
        });
    }
});
</script>
