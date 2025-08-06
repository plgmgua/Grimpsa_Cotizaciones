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

HTMLHelper::_('bootstrap.framework');
HTMLHelper::_('behavior.formvalidator');

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
                        <?php echo htmlspecialchars($this->item->name ?? 'Cotización'); ?>
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
                            <?php echo $isNew ? 'Nueva Cotización' : htmlspecialchars($this->item->name ?? 'Cotización'); ?>
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
                                               value="<?php echo htmlspecialchars($this->item->name ?? ''); ?>" 
                                               class="form-control" readonly />
                                        <small class="form-text text-muted">
                                            <?php if (empty($this->item->name) || $this->item->name === ''): ?>
                                                Se generará automáticamente al guardar
                                            <?php else: ?>
                                                Generado automáticamente por Odoo
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="jform_date_order" class="form-label">
                                            Fecha de Cotización *
                                        </label>
                                        <input type="date" name="jform[date_order]" id="jform_date_order" 
                                               value="<?php echo date('Y-m-d', strtotime($this->item->date_order ?? date('Y-m-d'))); ?>" 
                                               class="form-control required" required />
                                    </div>
                                </div>
                            </div>

                            <div class="row">
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <?php if ($isNew): ?>
                                            <label for="client_search" class="form-label">
                                                Cliente *
                                            </label>
                                            <div class="client-selector">
                                                <input type="text" id="client_search" 
                                                       class="form-control" 
                                                       placeholder="Buscar cliente por nombre..." 
                                                       autocomplete="off" />
                                                <div id="client_results" class="client-results" style="display: none;"></div>
                                                <input type="hidden" name="jform[partner_id]" id="jform_partner_id" 
                                                       value="<?php echo htmlspecialchars($this->item->partner_id ?? ''); ?>" 
                                                       class="required" required />
                                                <div id="selected_client" class="selected-client" style="display: none;">
                                                    <div class="alert alert-success">
                                                        <strong>Cliente seleccionado:</strong> <span id="selected_client_name"></span>
                                                        <button type="button" class="btn btn-sm btn-outline-danger ms-2" onclick="clearClientSelection()">
                                                            <i class="fas fa-times"></i> Cambiar
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                            <small class="form-text text-muted">Busque y seleccione un cliente existente</small>
                                        <?php else: ?>
                                            <label for="jform_client_name" class="form-label">
                                                Cliente
                                            </label>
                                            <input type="text" id="jform_client_name" 
                                                   value="<?php echo htmlspecialchars($this->item->contact_name ?? 'Cliente no disponible'); ?>" 
                                                   class="form-control" readonly />
                                            <input type="hidden" name="jform[partner_id]" 
                                                   value="<?php echo htmlspecialchars($this->item->partner_id ?? ''); ?>" />
                                            <small class="form-text text-muted">El cliente no se puede cambiar una vez creada la cotización</small>
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
                                                 class="form-control" rows="4"><?php echo htmlspecialchars($this->item->note ?? ''); ?></textarea>
                                    </div>
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
                                           value="<?php echo htmlspecialchars($this->item->amount_total ?? '0.00'); ?>" 
                                           class="form-control" readonly />
                                </div>
                                <small class="form-text text-muted">Se calcula automáticamente</small>
                            </div>

                            <div class="alert alert-info">
                                <h6><i class="fas fa-info-circle"></i> Información</h6>
                                <ul class="mb-0">
                                    <li>El número se genera automáticamente</li>
                                    <li>El total se calcula en Odoo</li>
                                    <li>Agente: <?php echo htmlspecialchars($user->name); ?></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Quote Lines Section - Only show if quote has been created -->
            <?php if (!empty($this->item->id) && $this->item->id > 0 && !empty($this->item->name)): ?>
            <div class="row mt-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">
                                <i class="fas fa-list"></i> Líneas de Cotización
                            </h5>
                        </div>
                        <div class="card-body">
                            <!-- Add Product Line Form -->
                            <div class="add-line-form">
                                <div class="card border-success">
                                    <div class="card-header bg-light">
                                        <h6 class="mb-0">
                                            <i class="fas fa-plus"></i> Agregar Producto
                                        </h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="product_name" class="form-label">Nombre del Producto *</label>
                                                    <input type="text" id="product_name" class="form-control" 
                                                           value="" readonly 
                                                           placeholder="Se generará automáticamente" />
                                                    <small class="form-text text-muted">Se genera automáticamente como PROD-001, PROD-002, etc.</small>
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="mb-3">
                                                    <label for="product_quantity" class="form-label">Cantidad *</label>
                                                    <input type="number" id="product_quantity" class="form-control" 
                                                           value="1" min="1" step="1" required />
                                                </div>
                                            </div>
                                            <div class="col-md-3">
                                                <div class="mb-3">
                                                    <label for="product_price" class="form-label">Precio Unitario *</label>
                                                    <div class="input-group">
                                                        <span class="input-group-text">Q</span>
                                                        <input type="number" id="product_price" class="form-control" 
                                                               step="0.01" min="0" placeholder="0.00" required />
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="row">
                                            <div class="col-md-12">
                                                <div class="mb-3">
                                                    <label for="product_description" class="form-label">Descripción del Producto</label>
                                                    <textarea id="product_description" class="form-control" rows="3" 
                                                              placeholder="Descripción detallada del producto o servicio..." required></textarea>
                                                    <small class="form-text text-muted">
                                                        Incluye especificaciones, materiales, dimensiones, etc. (Requerido)
                                                    </small>
                                                </div>
                                            </div>
                                        </div>
                                        <div class="text-end">
                                            <button type="button" class="btn btn-success" onclick="addQuoteLine()">
                                                <i class="fas fa-plus"></i> Agregar Línea
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <!-- Quote Lines Table -->
                            <div class="quote-lines-table mt-4">
                                <table class="table table-striped" id="quote-lines-table">
                                    <thead class="table-success">
                                        <tr>
                                            <th width="35%">Producto</th>
                                            <th width="15%">Cantidad</th>
                                            <th width="15%">Precio Unit.</th>
                                            <th width="15%">Subtotal</th>
                                            <th width="20%">Acciones</th>
                                        </tr>
                                    </thead>
                                    <tbody id="quote-lines-tbody">
                                        <!-- Quote lines will be loaded here -->
                                        <tr id="no-lines-row">
                                            <td colspan="5" class="text-center text-muted">
                                                <i class="fas fa-info-circle"></i> 
                                                No hay líneas agregadas. Use el formulario de arriba para agregar productos.
                                            </td>
                                        </tr>
                                    </tbody>
                                    <tfoot>
                                        <tr class="table-light">
                                            <th colspan="3" class="text-end">Total:</th>
                                            <th class="currency-amount" id="quote-total">Q 0.00</th>
                                            <th></th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php else: ?>
            <!-- Message for new quotes -->
            <div class="row mt-4">
                <div class="col-12">
                    <div class="alert alert-info">
                        <h6><i class="fas fa-info-circle"></i> Información Importante</h6>
                        <p class="mb-2">Para agregar productos a la cotización, primero debe:</p>
                        <ol class="mb-0">
                            <li><strong>Completar la información básica</strong> (Cliente y Fecha)</li>
                            <li><strong>Guardar la cotización</strong> para generar el número</li>
                            <li><strong>Luego podrá agregar productos</strong> y líneas de cotización</li>
                        </ol>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            <!-- Form Actions -->
            <div class="form-actions">
                <div class="btn-toolbar" role="toolbar">
                    <div class="btn-group me-2" role="group">
                        <?php if (empty($this->item->id) || $this->item->id <= 0): ?>
                            <button type="button" class="btn btn-success btn-lg" onclick="saveQuoteHeader()">
                                <i class="fas fa-save"></i> Crear Cotización
                            </button>
                        <?php else: ?>
                            <button type="button" class="btn btn-success" onclick="saveQuote()">
                                <i class="fas fa-save"></i> Guardar Cambios
                            </button>
                        <?php endif; ?>
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
        <?php echo HTMLHelper::_('form.token'); ?>
    </form>
</div>

<script>
// Client search functionality
let searchTimeout;
let selectedClientId = null;

document.addEventListener('DOMContentLoaded', function() {
    const clientSearch = document.getElementById('client_search');
    if (clientSearch) {
        clientSearch.addEventListener('input', function() {
            clearTimeout(searchTimeout);
            const query = this.value.trim();
            
            if (query.length < 2) {
                hideClientResults();
                return;
            }
            
            searchTimeout = setTimeout(() => {
                searchClients(query);
            }, 300);
        });
        
        // Hide results when clicking outside
        document.addEventListener('click', function(e) {
            if (!e.target.closest('.client-selector')) {
                hideClientResults();
            }
        });
    }
});

function searchClients(query) {
    // Show loading
    const resultsDiv = document.getElementById('client_results');
    resultsDiv.innerHTML = '<div class="client-result-item">Buscando...</div>';
    resultsDiv.style.display = 'block';
    
    // Make AJAX call to search clients
    fetch('<?php echo Route::_('index.php?option=com_cotizaciones&task=cotizacion.searchClients&format=json'); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: 'search=' + encodeURIComponent(query) + '&<?php echo Session::getFormToken(); ?>=1'
    })
    .then(response => response.json())
    .then(data => {
        displayClientResults(data.clients || []);
    })
    .catch(error => {
        console.error('Error searching clients:', error);
        resultsDiv.innerHTML = '<div class="client-result-item text-danger">Error al buscar clientes</div>';
    });
}

function displayClientResults(clients) {
    const resultsDiv = document.getElementById('client_results');
    
    if (clients.length === 0) {
        resultsDiv.innerHTML = '<div class="client-result-item text-muted">No se encontraron clientes</div>';
        return;
    }
    
    let html = '';
    clients.forEach(client => {
        html += `
            <div class="client-result-item" onclick="selectClient(${client.id}, '${escapeHtml(client.name)}')">
                <strong>${escapeHtml(client.name)}</strong>
                ${client.email ? '<br><small class="text-muted">' + escapeHtml(client.email) + '</small>' : ''}
            </div>
        `;
    });
    
    resultsDiv.innerHTML = html;
}

function selectClient(clientId, clientName) {
    selectedClientId = clientId;
    
    // Set hidden field
    document.getElementById('jform_partner_id').value = clientId;
    
    // Show selected client
    document.getElementById('selected_client_name').textContent = clientName;
    document.getElementById('selected_client').style.display = 'block';
    
    // Hide search
    document.getElementById('client_search').style.display = 'none';
    hideClientResults();
}

function clearClientSelection() {
    selectedClientId = null;
    document.getElementById('jform_partner_id').value = '';
    document.getElementById('selected_client').style.display = 'none';
    document.getElementById('client_search').style.display = 'block';
    document.getElementById('client_search').value = '';
    document.getElementById('client_search').focus();
}

function hideClientResults() {
    document.getElementById('client_results').style.display = 'none';
}

// Form submission functions
function saveQuote() {
    if (validateForm()) {
        document.querySelector('input[name="task"]').value = 'cotizacion.save';
        document.getElementById('adminForm').submit();
    }
}

function saveQuoteHeader() {
    if (validateForm()) {
        // Show loading state
        const btn = event.target;
        const originalText = btn.innerHTML;
        btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Creando Cotización...';
        btn.disabled = true;
        
        document.querySelector('input[name="task"]').value = 'cotizacion.save';
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

// Quote Lines Management
let quoteLines = [];
let lineCounter = 0;
let productCounter = 1;

// Generate next product name
function generateProductName() {
    const name = 'PROD-' + String(productCounter).padStart(3, '0');
    productCounter++;
    return name;
}

function addQuoteLine() {
    const description = document.getElementById('product_description').value.trim();
    const quantity = parseFloat(document.getElementById('product_quantity').value) || 1;
    const price = parseFloat(document.getElementById('product_price').value) || 0;
    
    // Validation
    if (!description) {
        alert('Por favor ingrese la descripción del producto');
        document.getElementById('product_description').focus();
        return;
    }
    
    if (quantity <= 0) {
        alert('La cantidad debe ser mayor a 0');
        document.getElementById('product_quantity').focus();
        return;
    }
    
    if (price <= 0) {
        alert('El precio debe ser mayor a 0');
        document.getElementById('product_price').focus();
        return;
    }
    
    // Generate product name
    const productName = generateProductName();
    document.getElementById('product_name').value = productName;
    
    // Create line object
    const line = {
        id: ++lineCounter,
        name: productName,
        description: description,
        quantity: quantity,
        price: price,
        subtotal: quantity * price
    };
    
    // Add to array
    quoteLines.push(line);
    
    // Send to Odoo
    const quoteId = <?php echo (int) ($this->item->id ?? 0); ?>;
    if (quoteId > 0) {
        createQuoteLineInOdoo(quoteId, productName, description, quantity, price);
    }
    
    // Update table
    updateQuoteLinesTable();
    
    // Clear form
    clearProductForm();
    
    // Focus back to description
    document.getElementById('product_description').focus();
}

function createQuoteLineInOdoo(quoteId, productName, description, quantity, price) {
    fetch('<?php echo Route::_('index.php?option=com_cotizaciones&task=cotizacion.createLine&format=json'); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
            'X-Requested-With': 'XMLHttpRequest'
        },
        body: `quote_id=${quoteId}&product_name=${encodeURIComponent(productName)}&description=${encodeURIComponent(description)}&quantity=${quantity}&price=${price}&<?php echo Session::getFormToken(); ?>=1`
    })
    .then(response => response.json())
    .then(data => {
        if (!data.success) {
            console.error('Error creating line in Odoo:', data.message);
            alert('Error al crear la línea en Odoo: ' + data.message);
        }
    })
    .catch(error => {
        console.error('Error:', error);
        alert('Error de conexión al crear la línea');
    });
}

function removeQuoteLine(lineId) {
    if (confirm('¿Está seguro de que desea eliminar esta línea?')) {
        quoteLines = quoteLines.filter(line => line.id !== lineId);
        updateQuoteLinesTable();
    }
}

function updateQuoteLinesTable() {
    const tbody = document.getElementById('quote-lines-tbody');
    const noLinesRow = document.getElementById('no-lines-row');
    
    if (quoteLines.length === 0) {
        tbody.innerHTML = '<tr id="no-lines-row"><td colspan="5" class="text-center text-muted"><i class="fas fa-info-circle"></i> No hay líneas agregadas. Use el formulario de arriba para agregar productos.</td></tr>';
        document.getElementById('quote-total').textContent = 'Q 0.00';
        return;
    }
    
    let html = '';
    let total = 0;
    
    quoteLines.forEach(line => {
        total += line.subtotal;
        html += `
            <tr>
                <td>
                    <strong>${escapeHtml(line.name)}</strong>
                    <br><small class="text-muted">${escapeHtml(line.description)}</small>
                </td>
                <td>${line.quantity}</td>
                <td>Q ${line.price.toFixed(2)}</td>
                <td class="currency-amount">Q ${line.subtotal.toFixed(2)}</td>
                <td>
                    <button type="button" class="btn btn-sm btn-outline-danger" onclick="removeQuoteLine(${line.id})">
                        <i class="fas fa-trash"></i> Eliminar
                    </button>
                </td>
            </tr>
        `;
    });
    
    tbody.innerHTML = html;
    document.getElementById('quote-total').textContent = 'Q ' + total.toFixed(2);
}

function clearProductForm() {
    document.getElementById('product_name').value = generateProductName();
    document.getElementById('product_quantity').value = '1';
    document.getElementById('product_price').value = '';
    document.getElementById('product_description').value = '';
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>