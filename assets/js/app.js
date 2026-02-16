/* ── Toast Notifications ─── */
function showToast(message, type = 'info', duration = 3500) {
    let container = document.getElementById('toast-container');
    if (!container) {
        container = document.createElement('div');
        container.id = 'toast-container';
        document.body.appendChild(container);
    }
    const toast = document.createElement('div');
    toast.className = `toast ${type}`;
    toast.textContent = message;
    container.appendChild(toast);
    setTimeout(() => {
        toast.style.opacity = '0';
        toast.style.transition = 'opacity .4s';
        setTimeout(() => toast.remove(), 400);
    }, duration);
}

/* ── AJAX Helper ── */
async function ajax(url, data = {}, method = 'POST') {
    const body = new URLSearchParams(data);
    const res = await fetch(url, {
        method,
        headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
        body: method !== 'GET' ? body : undefined,
    });
    if (!res.ok) throw new Error('Server error: ' + res.status);
    return res.json();
}

function confirmDelete(formId, msg = 'Are you sure you want to delete this item? This cannot be undone.') {
    const form = document.getElementById(formId);
    if (!form) return;
    form.addEventListener('submit', (e) => {
        if (!confirm(msg)) e.preventDefault();
    });
}

document.addEventListener('click', (e) => {
    const btn = e.target.closest('[data-confirm]');
    if (!btn) return;
    if (!confirm(btn.dataset.confirm)) e.preventDefault();
});

function openModal(id) {
    document.getElementById(id)?.classList.add('open');
}
function closeModal(id) {
    document.getElementById(id)?.classList.remove('open');
}

document.addEventListener('click', (e) => {
    if (e.target.classList.contains('modal-overlay')) {
        e.target.classList.remove('open');
    }
});

function setLoading(btn, loading, text = null) {
    if (loading) {
        btn.dataset.originalText = btn.innerHTML;
        btn.disabled = true;
        btn.innerHTML = `<svg class="spin" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 2v4M12 18v4M4.93 4.93l2.83 2.83M16.24 16.24l2.83 2.83M2 12h4M18 12h4M4.93 19.07l2.83-2.83M16.24 7.76l2.83-2.83"/></svg> ${text || 'Processing...'}`;
    } else {
        btn.disabled = false;
        btn.innerHTML = btn.dataset.originalText || (text || 'Submit');
    }
}

// Spinner CSS
const spinStyle = document.createElement('style');
spinStyle.textContent = `.spin { animation: spin .8s linear infinite; } @keyframes spin { to { transform: rotate(360deg); } }`;
document.head.appendChild(spinStyle);

async function previewEmail(templateId, recipientSelect, csrfToken) {
    const recipientEl = document.getElementById(recipientSelect);
    if (!recipientEl || !recipientEl.value) {
        showToast('Please select a recipient first.', 'error');
        return;
    }

    try {
        const data = await ajax('preview_email.php', {
            template_id: templateId,
            recipient_id: recipientEl.value,
            csrf_token: csrfToken,
        });
        if (data.success) {
            const iframe = document.getElementById('preview-iframe');
            iframe.srcdoc = data.html;
            document.getElementById('preview-subject').textContent = data.subject;
            openModal('preview-modal');
        } else {
            showToast(data.message, 'error');
        }
    } catch (err) {
        showToast('Preview failed.', 'error');
    }
}

function debounce(fn, delay = 300) {
    let timer;
    return (...args) => {
        clearTimeout(timer);
        timer = setTimeout(() => fn(...args), delay);
    };
}

document.querySelectorAll('.live-search').forEach(input => {
    input.addEventListener('input', debounce(() => {
        input.closest('form')?.submit();
    }));
});

async function toggleReminder(checkbox, csrfToken) {
    const url = checkbox.dataset.url || 'toggle_reminder.php';
    try {
        const data = await ajax(url, {
            enabled: checkbox.checked ? 1 : 0,
            csrf_token: csrfToken,
        });
        showToast(data.message, data.success ? 'success' : 'error');
        if (!data.success) checkbox.checked = !checkbox.checked;
    } catch {
        showToast('Failed to update setting.', 'error');
        checkbox.checked = !checkbox.checked;
    }
}
