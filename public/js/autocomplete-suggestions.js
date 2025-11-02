/**
 * Autocomplete Suggestions Component
 * Reusable autocomplete functionality for various input types
 */

class AutocompleteSuggestions {
    constructor() {
        this.setupScrollListeners();
    }

    // Setup scroll and resize listeners to hide dropdowns
    setupScrollListeners() {
        window.addEventListener('scroll', () => this.hideAll(), true);
        window.addEventListener('resize', () => this.hideAll());
    }

    // Hide all suggestion dropdowns
    hideAll() {
        document.querySelectorAll('.suggestions, .supplier-suggestions, .category-suggestions, .dept-suggestions')
            .forEach(el => el.classList.add('hidden'));
    }

    // Render item suggestions
    renderItemSuggestions(container, items, row, nameInput, priceInput, categoryInput) {
        if (!items.length) {
            container.classList.add('hidden');
            container.innerHTML = '';
            return;
        }
        container.innerHTML = items.map(it => `
            <div class="px-3 py-2 hover:bg-gray-50 cursor-pointer" onclick='selectSuggestion(${JSON.stringify(it).replace(/'/g, "&#39;")}, ${row.index})'>
                <div class="flex justify-between">
                    <span>${escapeHtml(it.name)} <span class="text-xs text-gray-500">(${escapeHtml(it.code)})</span></span>
                    <span class="text-xs text-green-600">Rp ${parseFloat(it.total_price||0).toLocaleString('id-ID')}${it.unit? ' / '+escapeHtml(it.unit.name):''}</span>
                </div>
                <div class="text-xs text-gray-500">${it.category? 'Kategori: '+escapeHtml(it.category.name):''}</div>
            </div>
        `).join('');
        positionDropdown(nameInput, container);
        container.classList.remove('hidden');
    }

    // Render category suggestions
    renderCategorySuggestions(container, categories, rowIndex) {
        const tr = document.getElementById('row-' + rowIndex);
        const input = tr.querySelector('.item-category');
        if (!categories.length) {
            container.innerHTML = '<div class="px-3 py-2 text-sm text-gray-500">Tidak ada hasil. Ketik untuk membuat kategori baru.</div>';
            positionDropdown(input, container);
            container.classList.remove('hidden');
            return;
        }
        container.innerHTML = categories.map(c => `
            <div class="px-3 py-2 hover:bg-gray-50 cursor-pointer" onclick='selectCategorySuggestion(${JSON.stringify(c).replace(/'/g, "&#39;")}, ${rowIndex})'>
                <div class="flex justify-between">
                    <span>${escapeHtml(c.name)}</span>
                </div>
            </div>
        `).join('');
        positionDropdown(input, container);
        container.classList.remove('hidden');
    }

    // Render supplier suggestions
    renderSupplierSuggestions(container, suppliers, rowIndex) {
        const tr = document.getElementById('row-' + rowIndex);
        const input = tr.querySelector('.alt-vendor');
        if (!suppliers.length) {
            container.classList.add('hidden');
            container.innerHTML = '';
            return;
        }
        container.innerHTML = suppliers.map(s => `
            <div class="px-3 py-2 hover:bg-gray-50 cursor-pointer" onclick='selectSupplierSuggestion(${JSON.stringify(s).replace(/'/g, "&#39;")}, ${rowIndex})'>
                <div class="flex justify-between">
                    <span>${escapeHtml(s.name)} <span class="text-xs text-gray-500">${s.code? '('+escapeHtml(s.code)+')':''}</span></span>
                    <span class="text-xs text-gray-500">${escapeHtml(s.email||'')}${s.phone? ' • '+escapeHtml(s.phone):''}</span>
                </div>
            </div>
        `).join('');
        positionDropdown(input, container);
        container.classList.remove('hidden');
    }

    // Render department suggestions
    renderDepartmentSuggestions(container, departments, rowIndex) {
        const tr = document.getElementById('row-' + rowIndex);
        const input = tr.querySelector('.allocation-dept');
        if (!departments.length) {
            container.innerHTML = '<div class="px-3 py-2 text-sm text-gray-500">Tidak ada hasil.</div>';
            positionDropdown(input, container);
            container.classList.remove('hidden');
            return;
        }
        container.innerHTML = departments.map(d => `
            <div class="px-3 py-2 hover:bg-gray-50 cursor-pointer" onclick='selectDepartmentSuggestion(${JSON.stringify(d).replace(/'/g, "&#39;")}, ${rowIndex})'>
                <div class="flex justify-between">
                    <span>${escapeHtml(d.name)}</span>
                </div>
            </div>
        `).join('');
        positionDropdown(input, container);
        container.classList.remove('hidden');
    }

    // Select item suggestion
    selectItemSuggestion(item, rowIndex) {
        const tr = document.getElementById('row-' + rowIndex);
        const nameInput = tr.querySelector('.item-name');
        const priceInput = tr.querySelector('.item-price');
        const categoryInput = tr.querySelector('.item-category');
        const sugBox = tr.querySelector('.suggestions');
        
        const row = rows.find(r => r.index === rowIndex);
        if (row) {
            row.master_item_id = item.id;
            row.name = item.name;
            // Do not auto-fetch price from DB; keep price as user input (empty by default)
            row.unit_price = row.unit_price;
            if (item.category) {
                row.item_category_id = item.category.id;
                row.item_category_name = item.category.name;
                if (categoryInput) categoryInput.value = item.category.name;
            }
            nameInput.value = item.name;
            // Leave price input unchanged (empty unless user provided)
        }
        sugBox.classList.add('hidden');
    }

    // Select category suggestion
    selectCategorySuggestion(category, rowIndex) {
        const tr = document.getElementById('row-' + rowIndex);
        const input = tr.querySelector('.item-category');
        const sug = tr.querySelector('.category-suggestions');
        const row = rows.find(r => r.index === rowIndex);
        if (row) {
            row.item_category_id = category.id;
            row.item_category_name = category.name;
        }
        input.value = category.name;
        sug.classList.add('hidden');
    }

    // Select supplier suggestion
    selectSupplierSuggestion(supplier, rowIndex) {
        const tr = document.getElementById('row-' + rowIndex);
        const input = tr.querySelector('.alt-vendor');
        const sug = tr.querySelector('.supplier-suggestions');
        const row = rows.find(r => r.index === rowIndex);
        if (row) {
            row.supplier_id = supplier.id;
            row.alternative_vendor = supplier.name;
        }
        input.value = supplier.name;
        sug.classList.add('hidden');
    }

    // Select department suggestion
    selectDepartmentSuggestion(department, rowIndex) {
        const tr = document.getElementById('row-' + rowIndex);
        const input = tr.querySelector('.allocation-dept');
        const sug = tr.querySelector('.dept-suggestions');
        const row = rows.find(r => r.index === rowIndex);
        if (row) {
            row.allocation_department_id = department.id;
            row.allocation_department_name = department.name;
        }
        input.value = department.name;
        sug.classList.add('hidden');
    }
}

// Initialize and export
if (typeof window !== 'undefined') {
    const autocompleteSuggestions = new AutocompleteSuggestions();
    
    // Export functions to window for onclick handlers
    window.renderSuggestions = (container, items, row, nameInput, priceInput, categoryInput) => 
        autocompleteSuggestions.renderItemSuggestions(container, items, row, nameInput, priceInput, categoryInput);
    window.renderCategorySuggestions = (container, categories, rowIndex) => 
        autocompleteSuggestions.renderCategorySuggestions(container, categories, rowIndex);
    window.renderSupplierSuggestions = (container, suppliers, rowIndex) => 
        autocompleteSuggestions.renderSupplierSuggestions(container, suppliers, rowIndex);
    window.renderDepartmentSuggestions = (container, departments, rowIndex) => 
        autocompleteSuggestions.renderDepartmentSuggestions(container, departments, rowIndex);
    
    window.selectSuggestion = (item, rowIndex) => 
        autocompleteSuggestions.selectItemSuggestion(item, rowIndex);
    window.selectCategorySuggestion = (category, rowIndex) => 
        autocompleteSuggestions.selectCategorySuggestion(category, rowIndex);
    window.selectSupplierSuggestion = (supplier, rowIndex) => 
        autocompleteSuggestions.selectSupplierSuggestion(supplier, rowIndex);
    window.selectDepartmentSuggestion = (department, rowIndex) => 
        autocompleteSuggestions.selectDepartmentSuggestion(department, rowIndex);
}
