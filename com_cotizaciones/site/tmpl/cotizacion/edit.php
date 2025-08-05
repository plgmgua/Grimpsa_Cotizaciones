<?php
/**
 * @package     Grimpsa.Site
 * @subpackage  com_cotizaciones
 *
 * @copyright   Copyright (C) 2025 Grimpsa. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Factory;
use Joomla\CMS\Session\Session;

HTMLHelper::_('bootstrap.framework');
HTMLHelper::_('behavior.formvalidator');

// Get the application input object
$app = Factory::getApplication();
$input = $app->input;

// Safe function to escape strings
function safeEscape($value, $default = '') {
    if (is_string($value) && !empty($value)) {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
    return $default;
}

// Safe function to get object property
function safeGetProperty($object, $property, $default = '') {
    if (is_object($object) && property_exists($object, $property)) {
        return safeEscape($object->$property, $default);
    } elseif (is_array($object) && isset($object[$property])) {
        return safeEscape($object[$property], $default);
    }
    return $default;
}

$isNew = (!isset($this->item->id) || (int)$this->item->id === 0);
$user = Factory::getUser();
?>

<div class="cotizaciones-component">
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1>
                    <?php if ($isNew): ?>
                        Nueva Cotización
                    <?php else: ?>
                        <?php echo safeGetProperty($this->item, 'name', 'Cotización'); ?>
                    <?php endif; ?>
                </h1>
                <nav aria-label="breadcrumb">
                    <ol class="breadcrumb">
                        <li class="breadcrumb-item">
                            <a href="<?php echo Route::_('index.php?option=com_cotizaciones&view=cotizaciones'); ?>">
                                Mis Cotizaciones
                            </a>
                        </li>
                        <li class="breadcrumb-item active" aria-current="page">
                            <?php echo $isNew ? 'Nueva Cotización' : safeGetProperty($this->item, 'name', 'Cotización'); ?>
                        </li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <form action="<?php echo Route::_('index.php?option=com_cotizaciones&view=cotizacion&layout=edit&id=' . (int) ($this->item->id ?? 0)); ?>" 
          method="post" name="adminForm" id="adminForm" class="form-validate">
        
        <div class="quote-form-container">
            <div class="row">
                <!-- Basic Information -->
                <div class="col-lg-8">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Información Básica</h5>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="jform_name" class="form-label">
                                            Número de Cotización
                                        </label>
                                        <input type="text" name="jform[name]" id="jform_name" 
                                               value="<?php echo safeGetProperty($this->item, 'name'); ?>" 
                                               class="form-control" readonly />
                                        <small class="form-text text-muted">Se genera automáticamente</small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="jform_date_order" class="form-label">
                                            Fecha de Cotización *
                                        </label>
                                        <input type="date" name="jform[date_order]" id="jform_date_order" 
                                               value="<?php echo date('Y-m-d', strtotime(safeGetProperty($this->item, 'date_order', date('Y-m-d')))); ?>" 
                                               class="form-control required" required />
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label for="client_search" class="form-label">
                                            Cliente *
                                        </label>
                                        <?php if ($isNew): ?>
                                            <!-- Client Search for New Quotes -->
                                            <div class="client-selector">
                                                <input type="hidden" name="jform[partner_id]" id="jform_partner_id" 
                                                       value="<?php echo safeGetProperty($this->item, 'partner_id'); ?>" 
                                                       class="required" required />
                                                <input type="text" id="client_search" 
                                                       class="form-control" 
                                                       placeholder="Escriba el nombre del cliente para buscar..." 
                                                       autocomplete="off" />
                                                <div id="client_results" class="client-results" style="display: none;"></div>
                                                <div id="selected_client" class="selected-client mt-2"></div>
                                            </div>
                                            <small class="form-text text-muted">Busque y seleccione un cliente</small>
                                        <?php else: ?>
                                            <!-- Display Selected Client for Existing Quotes -->
                                            <input type="hidden" name="jform[partner_id]" id="jform_partner_id" 
                                                   value="<?php echo safeGetProperty($this->item, 'partner_id'); ?>" />
                                            <div class="alert alert-info">
                                                <i class="fas fa-user"></i>
                                                <strong><?php echo safeGetProperty($this->item, 'contact_name', 'Cliente ID: ' . safeGetProperty($this->item, 'partner_id')); ?></strong>
                                                <?php if (!empty(safeGetProperty($this->item, 'contact_vat'))): ?>
                                                    <br><small>NIT: <?php echo safeGetProperty($this->item, 'contact_vat'); ?></small>
                                                <?php endif; ?>
                                                <?php if (!empty(safeGetProperty($this->item, 'contact_email'))): ?>
                                                    <br><small>Email: <?php echo safeGetProperty($this->item, 'contact_email'); ?></small>
                                                <?php endif; ?>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label for="jform_note" class="form-label">
                                            Notas
                                        </label>
                                        <textarea name="jform[note]" id="jform_note" 
                                                 class="form-control" rows="4"><?php echo safeGetProperty($this->item, 'note'); ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Quote Lines Section -->
                    <div class="card mt-4">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-list"></i> Líneas de la Cotización
                                </h5>
                                <button type="button" class="btn btn-success btn-sm" onclick="showAddLineForm()">
                                    <i class="fas fa-plus"></i> Agregar Línea
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <!-- Add Line Form -->
                            <div id="add_line_form" class="add-line-form mb-4" style="display: none;">
                                <div class="card border-success">
                                    <div class="card-header bg-success text-white">
                                        <h6 class="mb-0">Agregar Nueva Línea</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="line_description" class="form-label">Descripción *</label>
                                                    <textarea id="line_description" class="form-control" rows="3" 
                                                             maxlength="600"
                                                             placeholder="Descripción del producto/servicio (máximo 600 caracteres)"></textarea>
                                                    <small class="form-text text-muted">
                                                        <span id="char_count">0</span>/600 caracteres
                                                    </small>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="mb-3">
                                                    <label for="line_quantity" class="form-label">Cantidad *</label>
                                                    <input type="number" id="line_quantity" class="form-control" 
                                                           value="1.00" step="0.01" min="0.01" />
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="mb-3">
                                                    <label for="line_price" class="form-label">Precio *</label>
                                                    <input type="number" id="line_price" class="form-control" 
                                                           value="0.00" step="0.01" min="0" />
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="mb-3">
                                                    <label class="form-label">&nbsp;</label>
                                                    <div class="btn-group d-block">
                                                        <button type="button" class="btn btn-success btn-sm d-block mb-1" onclick="addQuoteLine()">
                                                            <i class="fas fa-plus"></i> Agregar
                                                        </button>
                                                        <button type="button" class="btn btn-secondary btn-sm d-block" onclick="hideAddLineForm()">
                                                            <i class="fas fa-times"></i> Cancelar
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Quote Lines Display -->
                            <div id="quote_lines_container">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i> 
                                    <?php if ($isNew): ?>
                                        Agregue líneas a su cotización usando el botón "Agregar Línea"
                                    <?php else: ?>
                                        Cargando líneas de cotización...
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Summary Information -->
                <div class="col-lg-4">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Resumen</h5>
                        </div>
                        <div class="card-body">
                            <div class="mb-3">
                                <label for="jform_amount_total" class="form-label">
                                    Total
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text">Q</span>
                                    <input type="text" name="jform[amount_total]" id="jform_amount_total" 
                                           value="<?php echo safeGetProperty($this->item, 'amount_total', '0.00'); ?>" 
                                           class="form-control" readonly />
                                </div>
                                <small class="form-text text-muted">Se calcula automáticamente</small>
                            </div>

                            <div class="alert alert-info">
                                <h6><i class="fas fa-info-circle"></i> Información</h6>
                                <ul class="mb-0">
                                    <li>El número se genera automáticamente</li>
                                    <li>El total se calcula en Odoo</li>
                                    <li>Agente: <?php echo safeEscape($user->name); ?></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Form Actions -->
            <div class="form-actions">
                <div class="btn-toolbar" role="toolbar">
                    <div class="btn-group me-2" role="group">
                        <button type="button" class="btn btn-success" onclick="saveQuote()">
                            <i class="fas fa-save"></i> Guardar
                        </button>
                        <button type="button" class="btn btn-primary" onclick="applyQuote()">
                            <i class="fas fa-check"></i> Aplicar
                        </button>
                    </div>
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-secondary" onclick="cancelQuote()">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- Hidden fields -->
        <input type="hidden" name="task" value="" />
        <input type="hidden" name="id" value="<?php echo (int) ($this->item->id ?? 0); ?>" />
        <input type="hidden" name="quote_lines_data" id="quote_lines_data" value="" />
        <?php echo HTMLHelper::_('form.token'); ?>
    </form>
</div>

<script>
// Global variables
let clientSearchTimeout;
let quoteLines = [];
let productCounter = 1;
let isNew = <?php echo $isNew ? 'true' : 'false'; ?>;

// Client search functionality
function searchClients() {
    const searchTerm = document.getElementById('client_search').value;
    if (searchTerm.length < 2) {
        document.getElementById('client_results').style.display = 'none';
        return;
    }
    
    const resultsDiv = document.getElementById('client_results');
    resultsDiv.innerHTML = '<div class="p-2"><i class="fas fa-spinner fa-spin"></i> Buscando...</div>';
    resultsDiv.style.display = 'block';
    
    fetch('<?php echo Route::_('index.php?option=com_cotizaciones&task=cotizacion.searchClients'); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'search=' + encodeURIComponent(searchTerm) + '&<?php echo Session::getFormToken(); ?>=1'
    })
    .then(response => response.json())
    .then(data => {
        if (data.success && data.clients && data.clients.length > 0) {
            displayClientResults(data.clients);
        } else {
            resultsDiv.innerHTML = '<div class="p-2 text-muted">No se encontraron clientes</div>';
        }
    })
    .catch(error => {
        resultsDiv.innerHTML = '<div class="p-2 text-danger">Error en la búsqueda</div>';
    });
}

function displayClientResults(clients) {
    const resultsDiv = document.getElementById('client_results');
    let html = '';
    
    clients.forEach(client => {
        const clientName = client.name || 'Sin nombre';
        const clientVat = client.vat || '';
        const clientEmail = client.email || '';
        
        html += `<div class="client-result-item" onclick="selectClient(${client.id}, '${clientName.replace(/'/g, "\\'")}', '${clientVat}', '${clientEmail}')">
            <strong>${clientName}</strong>
            ${clientVat ? `<br><small class="text-muted">NIT: ${clientVat}</small>` : ''}
            ${clientEmail ? `<br><small class="text-muted">${clientEmail}</small>` : ''}
        </div>`;
    });
    
    resultsDiv.innerHTML = html;
}

function selectClient(id, name, vat, email) {
    document.getElementById('jform_partner_id').value = id;
    document.getElementById('client_search').value = '';
    document.getElementById('client_results').style.display = 'none';
    
    const selectedDiv = document.getElementById('selected_client');
    selectedDiv.innerHTML = `<div class="alert alert-success">
        <i class="fas fa-check-circle"></i> 
        <strong>${name}</strong>
        ${vat ? `<br><small>NIT: ${vat}</small>` : ''}
        ${email ? `<br><small>Email: ${email}</small>` : ''}
        <button type="button" class="btn btn-sm btn-outline-danger ms-2" onclick="clearSelectedClient()">
            <i class="fas fa-times"></i>
        </button>
    </div>`;
}

function clearSelectedClient() {
    document.getElementById('jform_partner_id').value = '';
    document.getElementById('selected_client').innerHTML = '';
}

// Quote lines functionality
function showAddLineForm() {
    document.getElementById('add_line_form').style.display = 'block';
    document.getElementById('line_description').focus();
}

function hideAddLineForm() {
    document.getElementById('add_line_form').style.display = 'none';
    clearLineForm();
}

function clearLineForm() {
    document.getElementById('line_description').value = '';
    document.getElementById('line_quantity').value = '1.00';
    document.getElementById('line_price').value = '0.00';
    updateCharCount();
}

function addQuoteLine() {
    const description = document.getElementById('line_description').value.trim();
    const quantity = parseFloat(document.getElementById('line_quantity').value) || 1;
    const price = parseFloat(document.getElementById('line_price').value) || 0;
    
    if (!description) {
        alert('La descripción es requerida');
        return;
    }
    
    if (description.length > 600) {
        alert('La descripción no puede exceder 600 caracteres');
        return;
    }
    
    const newLine = {
        id: 'temp_' + Date.now(),
        product_code: productCounter,
        description: description,
        quantity: quantity,
        price: price,
        is_new: true
    };
    
    quoteLines.push(newLine);
    productCounter++;
    
    hideAddLineForm();
    displayQuoteLines();
    updateTotal();
}

function displayQuoteLines() {
    const container = document.getElementById('quote_lines_container');
    
    if (quoteLines.length === 0) {
        container.innerHTML = '<div class="alert alert-info"><i class="fas fa-info-circle"></i> Agregue líneas a su cotización usando el botón "Agregar Línea"</div>';
        return;
    }
    
    let html = '<div class="table-responsive"><table class="table table-striped quote-lines-table">';
    html += '<thead class="table-success"><tr>';
    html += '<th>Código</th><th>Descripción</th><th>Cantidad</th><th>Precio Unit.</th><th>Subtotal</th><th>Acciones</th>';
    html += '</tr></thead><tbody>';
    
    quoteLines.forEach((line, index) => {
        const subtotal = line.quantity * line.price;
        html += `<tr>
            <td><span class="product-badge">PROD-${line.product_code}</span></td>
            <td>${line.description}</td>
            <td>${parseFloat(line.quantity).toFixed(2)}</td>
            <td class="currency-amount">Q ${parseFloat(line.price).toFixed(2)}</td>
            <td><strong class="currency-amount">Q ${subtotal.toFixed(2)}</strong></td>
            <td>
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeQuoteLine(${index})">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>`;
    });
    
    html += '</tbody></table></div>';
    container.innerHTML = html;
}

function removeQuoteLine(index) {
    if (confirm('¿Eliminar esta línea de la cotización?')) {
        quoteLines.splice(index, 1);
        displayQuoteLines();
        updateTotal();
    }
}

function updateTotal() {
    let total = 0;
    quoteLines.forEach(line => {
        total += line.quantity * line.price;
    });
    document.getElementById('jform_amount_total').value = total.toFixed(2);
}

function updateCharCount() {
    const description = document.getElementById('line_description').value;
    const count = description.length;
    document.getElementById('char_count').textContent = count;
    
    const charCountElement = document.getElementById('char_count');
    if (count > 600) {
        charCountElement.style.color = 'red';
    } else if (count > 500) {
        charCountElement.style.color = 'orange';
    } else {
        charCountElement.style.color = 'inherit';
    }
}

// Form submission functions
function saveQuote() {
    if (validateForm()) {
        document.getElementById('quote_lines_data').value = JSON.stringify(quoteLines);
        document.querySelector('input[name="task"]').value = 'cotizacion.save';
        document.getElementById('adminForm').submit();
    }
}

function applyQuote() {
    if (validateForm()) {
        document.getElementById('quote_lines_data').value = JSON.stringify(quoteLines);
        document.querySelector('input[name="task"]').value = 'cotizacion.apply';
        document.getElementById('adminForm').submit();
    }
}

function cancelQuote() {
    if (confirm('¿Está seguro de que desea cancelar? Se perderán los cambios no guardados.')) {
        window.location.href = '<?php echo Route::_('index.php?option=com_cotizaciones&view=cotizaciones'); ?>';
    }
}

function validateForm() {
    const partnerId = document.getElementById('jform_partner_id').value;
    const dateOrder = document.getElementById('jform_date_order').value;
    
    if (!partnerId) {
        alert('Debe seleccionar un cliente');
        return false;
    }
    
    if (!dateOrder) {
        alert('Debe seleccionar una fecha');
        return false;
    }
    
    return true;
}

// Load existing quote lines for edit mode
function loadExistingQuoteLines() {
    if (!isNew) {
        const quoteId = <?php echo (int)($this->item->id ?? 0); ?>;
        
        fetch('<?php echo Route::_('index.php?option=com_cotizaciones&task=cotizacion.getLines'); ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: 'quote_id=' + quoteId + '&<?php echo Session::getFormToken(); ?>=1'
        })
        .then(response => response.json())
        .then(data => {
            if (data.success && data.lines) {
                // Convert existing lines to our format
                data.lines.forEach(line => {
                    quoteLines.push({
                        id: line.id,
                        product_code: line.product_id || productCounter,
                        description: line.name || line.description || '',
                        quantity: parseFloat(line.product_uom_qty || line.quantity || 1),
                        price: parseFloat(line.price_unit || line.price || 0),
                        is_new: false
                    });
                    productCounter++;
                });
                displayQuoteLines();
                updateTotal();
            }
        })
        .catch(error => {
            console.error('Error loading quote lines:', error);
        });
    }
}

// Event listeners
document.addEventListener('DOMContentLoaded', function() {
    // Client search
    const clientSearchInput = document.getElementById('client_search');
    if (clientSearchInput) {
        clientSearchInput.addEventListener('input', function() {
            clearTimeout(clientSearchTimeout);
            clientSearchTimeout = setTimeout(searchClients, 300);
        });
    }
    
    // Character count for description
    const descriptionInput = document.getElementById('line_description');
    if (descriptionInput) {
        descriptionInput.addEventListener('input', updateCharCount);
    }
    
    // Hide client results when clicking outside
    document.addEventListener('click', function(e) {
        if (!e.target.closest('.client-selector')) {
            const resultsDiv = document.getElementById('client_results');
            if (resultsDiv) {
                resultsDiv.style.display = 'none';
            }
        }
    });
    
    // Load existing quote lines if editing
    loadExistingQuoteLines();
    
    // Initialize display
    displayQuoteLines();
});
</script>