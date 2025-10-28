(function(){
  const ensureRoot = () => {
    let root = document.getElementById('toast-root');
    if (!root) {
      root = document.createElement('div');
      root.id = 'toast-root';
      root.className = 'fixed top-4 right-4 z-[9999] space-y-2 pointer-events-none';
      document.body.appendChild(root);
    }
    return root;
  };

  const icons = {
    success: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>',
    error: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5.07 19h13.86A2.07 2.07 0 0021 16.93V7.07A2.07 2.07 0 0018.93 5H5.07A2.07 2.07 0 003 7.07v9.86A2.07 2.07 0 005.07 19z"></path></svg>',
    info: '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M12 2a10 10 0 100 20 10 10 0 000-20z"></path></svg>'
  };

  const styles = {
    base: 'pointer-events-auto flex items-start gap-2 w-80 max-w-[90vw] px-3 py-2 rounded-md shadow-md border transition transform',
    success: 'bg-green-50 border-green-200 text-green-800',
    error: 'bg-red-50 border-red-200 text-red-800',
    info: 'bg-blue-50 border-blue-200 text-blue-800'
  };

  const show = (type, message, opts={}) => {
    const root = ensureRoot();
    const el = document.createElement('div');
    el.className = `${styles.base} ${styles[type] || styles.info} opacity-0 translate-y-2`;

    const closeBtn = document.createElement('button');
    closeBtn.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"></path></svg>';
    closeBtn.className = 'ml-auto shrink-0 text-current/70 hover:text-current';
    closeBtn.onclick = () => dismiss();

    el.innerHTML = `
      <span class="mt-0.5">${icons[type] || icons.info}</span>
      <div class="text-sm">${escapeHtml(String(message || ''))}</div>
    `;
    el.appendChild(closeBtn);

    root.appendChild(el);

    requestAnimationFrame(() => {
      el.classList.remove('opacity-0','translate-y-2');
    });

    const duration = opts.duration ?? 4000;
    let timer = setTimeout(() => dismiss(), duration);

    el.addEventListener('mouseenter', () => clearTimeout(timer));
    el.addEventListener('mouseleave', () => timer = setTimeout(() => dismiss(), 1200));

    function dismiss(){
      el.classList.add('opacity-0','translate-y-2');
      setTimeout(() => el.remove(), 200);
    }

    return { dismiss };
  };

  function escapeHtml(s){
    return s
      .replace(/&/g,'&amp;')
      .replace(/</g,'&lt;')
      .replace(/>/g,'&gt;')
      .replace(/"/g,'&quot;')
      .replace(/'/g,'&#039;');
  }

  window.Toast = {
    success: (msg, opts) => show('success', msg, opts),
    error: (msg, opts) => show('error', msg, opts),
    info: (msg, opts) => show('info', msg, opts),
  };
})();
