/* ============================================
   Email Platform - Main JavaScript
   ============================================ */

// ---- CSRF Token Helper ----
function getCSRFHeader() {
    var meta = document.querySelector('meta[name="csrf-token"]');
    return meta ? meta.getAttribute('content') : '';
}

function updateCSRFToken(newToken) {
    var meta = document.querySelector('meta[name="csrf-token"]');
    if (meta && newToken) {
        meta.setAttribute('content', newToken);
    }
}

document.addEventListener('DOMContentLoaded', function () {

    // ---- Sidebar Toggle (Mobile) ----
    const sidebarToggle = document.getElementById('sidebarToggle');
    const sidebar = document.getElementById('sidebar');
    const overlay = document.getElementById('sidebarOverlay');

    if (sidebarToggle) {
        sidebarToggle.addEventListener('click', function () {
            sidebar.classList.toggle('open');
            overlay.classList.toggle('active');
        });
    }

    if (overlay) {
        overlay.addEventListener('click', function () {
            sidebar.classList.remove('open');
            overlay.classList.remove('active');
        });
    }

    // ---- Domain Selector ----
    const domainSelector = document.getElementById('domainSelector');
    if (domainSelector) {
        domainSelector.addEventListener('change', function () {
            const domainId = this.value;
            if (!domainId) return;

            fetch(BASE_URL + 'domains/set-active/' + domainId, {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'X-Requested-With': 'XMLHttpRequest',
                    'X-CSRF-TOKEN': getCSRFHeader()
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.csrf_token) updateCSRFToken(data.csrf_token);
                if (data.success) {
                    location.reload();
                }
            })
            .catch(err => console.error('Domain switch error:', err));
        });
    }

    // ---- Toast Notifications ----
    initToasts();

    // ---- Delete Confirmations ----
    document.querySelectorAll('[data-confirm]').forEach(function (el) {
        el.addEventListener('click', function (e) {
            const message = this.getAttribute('data-confirm') || 'Are you sure?';
            if (!confirm(message)) {
                e.preventDefault();
                e.stopPropagation();
            }
        });
    });

    // ---- POST delete forms ----
    document.querySelectorAll('.delete-form').forEach(function (form) {
        form.addEventListener('submit', function (e) {
            if (!confirm('Are you sure you want to delete this item? This action cannot be undone.')) {
                e.preventDefault();
            }
        });
    });
});

// ---- Toast System ----
function initToasts() {
    const container = document.getElementById('toastContainer');
    if (!container) return;

    container.querySelectorAll('.toast-notification').forEach(function (toast) {
        setTimeout(function () {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(100%)';
            setTimeout(function () { toast.remove(); }, 300);
        }, 5000);

        const closeBtn = toast.querySelector('.toast-close');
        if (closeBtn) {
            closeBtn.addEventListener('click', function () {
                toast.style.opacity = '0';
                toast.style.transform = 'translateX(100%)';
                setTimeout(function () { toast.remove(); }, 300);
            });
        }
    });
}

function showToast(message, type) {
    type = type || 'info';
    const container = document.getElementById('toastContainer');
    if (!container) return;

    const icons = {
        success: 'fa-check-circle',
        error: 'fa-exclamation-circle',
        warning: 'fa-exclamation-triangle',
        info: 'fa-info-circle'
    };

    const toast = document.createElement('div');
    toast.className = 'toast-notification ' + type;
    toast.innerHTML =
        '<i class="fas ' + (icons[type] || icons.info) + '"></i>' +
        '<span>' + message + '</span>' +
        '<button class="toast-close">&times;</button>';

    container.appendChild(toast);

    const closeBtn = toast.querySelector('.toast-close');
    closeBtn.addEventListener('click', function () {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(100%)';
        setTimeout(function () { toast.remove(); }, 300);
    });

    setTimeout(function () {
        toast.style.opacity = '0';
        toast.style.transform = 'translateX(100%)';
        setTimeout(function () { toast.remove(); }, 300);
    }, 5000);
}

// ---- Loading spinner helper ----
function showLoading(btn) {
    if (!btn) return;
    btn.disabled = true;
    btn.dataset.originalText = btn.innerHTML;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Loading...';
}

function hideLoading(btn) {
    if (!btn || !btn.dataset.originalText) return;
    btn.disabled = false;
    btn.innerHTML = btn.dataset.originalText;
}
