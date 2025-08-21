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
    const forms = document.querySelectorAll('.form-validate');
    forms.forEach(function(form) {
        form.addEventListener('submit', function(e) {
            const requiredFields = form.querySelectorAll('.required, [required]');
            let isValid = true;
            let firstInvalidField = null;

            requiredFields.forEach(function(field) {
                const value = field.value.trim();
                const fieldType = field.type;
                
                // Reset validation state
                field.classList.remove('is-invalid');
                field.classList.remove('is-valid');
                
                // Check if field is empty
                if (!value) {
                    field.classList.add('is-invalid');
                    isValid = false;
                    if (!firstInvalidField) firstInvalidField = field;
                } else {
                    // Additional validation based on field type
                    if (fieldType === 'email' && !isValidEmail(value)) {
                        field.classList.add('is-invalid');
                        isValid = false;
                        if (!firstInvalidField) firstInvalidField = field;
                    } else if (fieldType === 'number' && isNaN(value)) {
                        field.classList.add('is-invalid');
                        isValid = false;
                        if (!firstInvalidField) firstInvalidField = field;
                    } else {
                        field.classList.add('is-valid');
                    }
                }
            });

            if (!isValid) {
                e.preventDefault();
                if (firstInvalidField) {
                    firstInvalidField.focus();
                }
                showValidationMessage('Por favor complete todos los campos requeridos correctamente.');
            }
        });
    });

    // Email validation helper
    function isValidEmail(email) {
        const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
        return emailRegex.test(email);
    }

    // Show validation message
    function showValidationMessage(message) {
        // Remove existing validation messages
        const existingMessages = document.querySelectorAll('.validation-message');
        existingMessages.forEach(function(msg) {
            msg.remove();
        });

        // Create new message
        const messageDiv = document.createElement('div');
        messageDiv.className = 'alert alert-danger validation-message';
        messageDiv.innerHTML = '<i class="fas fa-exclamation-triangle"></i> ' + message;
        
        // Insert at the top of the form
        const form = document.querySelector('.form-validate');
        if (form) {
            form.insertBefore(messageDiv, form.firstChild);
            
            // Auto-remove after 5 seconds
            setTimeout(function() {
                messageDiv.remove();
            }, 5000);
        }
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

/**
 * Client search functionality
 */
document.addEventListener('DOMContentLoaded', function() {
    const clientSearch = document.getElementById('client_search');
    const clientSelect = document.getElementById('jform_partner_id');
    
    if (clientSearch && clientSelect) {
        clientSearch.addEventListener('input', function(e) {
            const searchTerm = e.target.value.toLowerCase().trim();
            const options = clientSelect.querySelectorAll('option');
            
            options.forEach(function(option) {
                if (option.value === '') {
                    // Always show the placeholder option
                    option.style.display = '';
                    return;
                }
                
                const searchData = option.getAttribute('data-search') || '';
                const optionText = option.textContent.toLowerCase();
                
                if (searchTerm === '' || searchData.includes(searchTerm) || optionText.includes(searchTerm)) {
                    option.style.display = '';
                } else {
                    option.style.display = 'none';
                }
            });
            
            // If search term is empty, reset to first option
            if (searchTerm === '') {
                clientSelect.selectedIndex = 0;
            }
        });
        
        // Clear search when select changes
        clientSelect.addEventListener('change', function() {
            if (clientSearch) {
                clientSearch.value = '';
                // Show all options again
                const options = clientSelect.querySelectorAll('option');
                options.forEach(function(option) {
                    option.style.display = '';
                });
            }
        });
        
        // Focus on search input when clicking on the select
        clientSelect.addEventListener('click', function() {
            if (clientSearch) {
                clientSearch.focus();
            }
        });
    }
});