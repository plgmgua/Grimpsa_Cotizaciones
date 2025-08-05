/**
 * Cotizaciones Component JavaScript
 */

document.addEventListener('DOMContentLoaded', function() {
    // Initialize tooltips if Bootstrap is available
    if (typeof bootstrap !== 'undefined' && bootstrap.Tooltip) {
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }

    // Auto-submit search form on Enter key
    const searchInput = document.getElementById('filter_search');
    if (searchInput) {
        searchInput.addEventListener('keypress', function(e) {
            if (e.key === 'Enter') {
                e.preventDefault();
                document.getElementById('adminForm').submit();
            }
        });
    }

    // Form validation enhancement
    const form = document.getElementById('adminForm');
    if (form && form.classList.contains('form-validate')) {
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('.required');
            let isValid = true;

            requiredFields.forEach(function(field) {
                if (!field.value.trim()) {
                    field.classList.add('is-invalid');
                    isValid = false;
                } else {
                    field.classList.remove('is-invalid');
                }
            });

            if (!isValid) {
                e.preventDefault();
                alert('Por favor complete todos los campos requeridos.');
            }
        });
    }

    // Partner ID validation
    const partnerIdField = document.getElementById('jform_partner_id');
    if (partnerIdField) {
        partnerIdField.addEventListener('input', function(e) {
            const value = e.target.value;
            if (value && (isNaN(value) || parseInt(value) <= 0)) {
                e.target.classList.add('is-invalid');
            } else {
                e.target.classList.remove('is-invalid');
            }
        });
    }
});

/**
 * Refresh quotes list
 */
function refreshQuotes() {
    // Add loading state
    const refreshBtn = document.querySelector('[onclick="refreshQuotes()"]');
    if (refreshBtn) {
        const originalText = refreshBtn.innerHTML;
        refreshBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Actualizando...';
        refreshBtn.disabled = true;
        
        // Reload the page
        setTimeout(() => {
            window.location.reload();
        }, 500);
    }
}

/**
 * Export quotes (placeholder)
 */
function exportQuotes() {
    if (confirm('¿Exportar todas tus cotizaciones a formato CSV?')) {
        // TODO: Implement actual export functionality
        alert('Funcionalidad de exportación será implementada aquí.\n\nEsto generará un archivo CSV con todas tus cotizaciones.');
        
        // Future implementation might look like:
        // window.location.href = 'index.php?option=com_cotizaciones&task=cotizaciones.export&format=csv';
    }
}

/**
 * Show loading state
 */
function showLoading(element) {
    if (element) {
        element.classList.add('loading');
        const spinner = document.createElement('div');
        spinner.className = 'spinner-border spinner-border-sm me-2';
        spinner.setAttribute('role', 'status');
        element.insertBefore(spinner, element.firstChild);
    }
}

/**
 * Hide loading state
 */
function hideLoading(element) {
    if (element) {
        element.classList.remove('loading');
        const spinner = element.querySelector('.spinner-border');
        if (spinner) {
            spinner.remove();
        }
    }
}