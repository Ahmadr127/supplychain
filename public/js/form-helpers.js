/**
 * Form Helper Functions
 * Reusable utility functions for forms
 */

// HTML escaping helper
function escapeHtml(text) {
    if (text == null) return '';
    const map = {
        '&': '&amp;',
        '<': '&lt;',
        '>': '&gt;',
        '"': '&quot;',
        "'": '&#039;'
    };
    return text.toString().replace(/[&<>"']/g, function(m) {
        return map[m];
    });
}

// Position dropdown using fixed positioning (viewport-based)
function positionDropdown(input, dropdown) {
    const rect = input.getBoundingClientRect();
    dropdown.style.top = rect.bottom + 'px';
    dropdown.style.left = rect.left + 'px';
    dropdown.style.width = rect.width + 'px';
}

// Rupiah formatting helpers
function formatRupiahInputValue(val) {
    if (val === '' || val === null || typeof val === 'undefined') return '';
    let digits = String(val).replace(/\D/g, '');
    if (!digits) return '';
    digits = digits.replace(/^0+(\d)/, '$1');
    return digits.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}

function parseRupiahToNumber(str) {
    if (str == null) return '';
    let digits = String(str).replace(/\D/g, '');
    return digits;
}

// Hide all suggestion dropdowns
function hideAllSuggestions() {
    document.querySelectorAll('.suggestions, .supplier-suggestions, .category-suggestions, .dept-suggestions')
        .forEach(el => el.classList.add('hidden'));
}

// Export functions to window for global access
if (typeof window !== 'undefined') {
    window.escapeHtml = escapeHtml;
    window.positionDropdown = positionDropdown;
    window.formatRupiahInputValue = formatRupiahInputValue;
    window.parseRupiahToNumber = parseRupiahToNumber;
    window.hideAllSuggestions = hideAllSuggestions;
}
