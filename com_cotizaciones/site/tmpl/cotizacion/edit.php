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

use Joomla\CMS\Session\Session;

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
                                        <label for="jform_partner_id" class="form-label">
                                            Cliente *
                                        </label>
                                        <?php if ($isNew): ?>
                                            <!-- Client Selector for New Quotes -->
                                            <div class="client-selector">
                                                <input type="hidden" name="jform[partner_id]" id="jform_partner_id" 
                                                       value="<?php echo safeGetProperty($this->item, 'partner_id'); ?>" 
                                                       class="required" required />
                                                <input type="text" id="client_search" 
                                                       class="form-control" 
                                                       placeholder="Buscar cliente por nombre o NIT..." />
                                                <div id="client_results" class="client-results" style="display: none;"></div>
                                                <div id="selected_client" class="selected-client"></div>
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
                                <div class="col-md-6">
                                    <div class="mb-3">
                                        <label for="jform_state" class="form-label">
                                            Estado
                                        </label>
                                        <select name="jform[state]" id="jform_state" class="form-select">
                                            <option value="draft" <?php echo (safeGetProperty($this->item, 'state') == 'draft') ? 'selected' : ''; ?>>
                                                Borrador
                                            </option>
                                            <option value="sent" <?php echo (safeGetProperty($this->item, 'state') == 'sent') ? 'selected' : ''; ?>>
                                                Enviada
                                            </option>
                                            <option value="sale" <?php echo (safeGetProperty($this->item, 'state') == 'sale') ? 'selected' : ''; ?>>
                                                Confirmada
                                            </option>
                                            <option value="done" <?php echo (safeGetProperty($this->item, 'state') == 'done') ? 'selected' : ''; ?>>
                                                Completada
                                            </option>
                                            <option value="cancel" <?php echo (safeGetProperty($this->item, 'state') == 'cancel') ? 'selected' : ''; ?>>
                                                Cancelada
                                            </option>
                                        </select>
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
                    <?php if (!$isNew): ?>
                    <div class="card mt-4">
                        <div class="card-header">
                            <div class="d-flex justify-content-between align-items-center">
                                <h5 class="card-title mb-0">
                                    <i class="fas fa-list"></i> Líneas de la Cotización
                                </h5>
                                <button type="button" class="btn btn-success btn-sm" onclick="addQuoteLine()">
                                    <i class="fas fa-plus"></i> Agregar Línea
                                </button>
                            </div>
                        </div>
                        <div class="card-body">
                            <div id="quote_lines_container">
                                <div class="alert alert-info">
                                    <i class="fas fa-info-circle"></i> Cargando líneas de cotización...
                                </div>
                            </div>

                            <!-- Add Line Form (Hidden by default) -->
                            <div id="add_line_form" class="add-line-form" style="display: none;">
                                <div class="card border-success">
                                    <div class="card-header bg-success text-white">
                                        <h6 class="mb-0">Agregar Nueva Línea</h6>
                                    </div>
                                    <div class="card-body">
                                        <div class="row">
                                            <div class="col-md-6">
                                                <div class="mb-3">
                                                    <label for="line_description" class="form-label">Descripción *</label>
                                                    <textarea id="line_description" class="form-control" rows="2" 
                                                             placeholder="Descripción del producto/servicio"></textarea>
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="mb-3">
                                                    <label for="line_quantity" class="form-label">Cantidad</label>
                                                    <input type="number" id="line_quantity" class="form-control" 
                                                           value="1.00" step="0.01" min="0.01" />
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="mb-3">
                                                    <label for="line_price" class="form-label">Precio Unit.</label>
                                                    <input type="number" id="line_price" class="form-control" 
                                                           value="0.00" step="0.01" min="0" />
                                                </div>
                                            </div>
                                            <div class="col-md-2">
                                                <div class="mb-3">
                                                    <label class="form-label">&nbsp;</label>
                                                    <div class="btn-group d-block">
                                                        <button type="button" class="btn btn-success btn-sm" onclick="saveQuoteLine()">
                                                            <i class="fas fa-save"></i> Guardar
                                                        </button>
                                                        <button type="button" class="btn btn-secondary btn-sm" onclick="cancelAddLine()">
                                                            <i class="fas fa-times"></i> Cancelar
                                                        </button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
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
                        <button type="button" class="btn btn-success" onclick="Joomla.submitbutton('cotizacion.save')">
                            <i class="fas fa-save"></i> Guardar
                        </button>
                        <button type="button" class="btn btn-primary" onclick="Joomla.submitbutton('cotizacion.apply')">
                            <i class="fas fa-check"></i> Aplicar
                        </button>
                    </div>
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-secondary" onclick="Joomla.submitbutton('cotizacion.cancel')">
                            <i class="fas fa-times"></i> Cancelar
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <input type="hidden" name="task" value="" />
        <input type="hidden" name="id" value="<?php echo (int) ($this->item->id ?? 0); ?>" />
        <?php echo HTMLHelper::_('form.token'); ?>
    </form>
</div>

<script>
// Global variables
let clientSearchTimeout;
let quoteLines = []; // Store lines for new quotes
let nextProductNumber = 1; // For correlative product numbers

// Client selector functionality
let clientSearchTimeout;

function searchClients() {
    const searchTerm = document.getElementById('client_search').value;
    if (searchTerm.length < 2) {
        document.getElementById('client_results').style.display = 'none';
        return;
    }
    
    // Show loading
    const resultsDiv = document.getElementById('client_results');
    resultsDiv.innerHTML = '<div class="p-2"><i class="fas fa-spinner fa-spin"></i> Buscando...</div>';
    resultsDiv.style.display = 'block';
    
    // Make AJAX call to search clients
    fetch('<?php echo Route::_('index.php?option=com_cotizaciones&task=cotizacion.searchClients'); ?>', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/x-www-form-urlencoded',
        },
        body: 'search=' + encodeURIComponent(searchTerm) + '&<?php echo Session::getFormToken(); ?>=1'
    .then(response => response.json())
    .then(data => {
        if (data.success && data.clients) {
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
        html += `<div class="client-result-item" onclick="selectClient(${client.id}, '${client.name.replace(/'/g, "\\'")}', '${client.vat || ''}')">
            <strong>${client.name}</strong>
            ${client.vat ? `<br><small class="text-muted">NIT: ${client.vat}</small>` : ''}
            ${client.email ? `<br><small class="text-muted">${client.email}</small>` : ''}
        </div>`;
    });
    
    resultsDiv.innerHTML = html;
}

function selectClient(id, name, vat) {
    document.getElementById('jform_partner_id').value = id;
    document.getElementById('client_search').value = '';
    document.getElementById('client_results').style.display = 'none';
    
    const selectedDiv = document.getElementById('selected_client');
    selectedDiv.innerHTML = `<div class="alert alert-success">
        <i class="fas fa-check-circle"></i> 
        <strong>${name}</strong>
        ${vat ? `<br><small>NIT: ${vat}</small>` : ''}
        <button type="button" class="btn btn-sm btn-outline-danger ms-2" onclick="clearSelectedClient()">
            <i class="fas fa-times"></i>
        </button>
    </div>`;
}

function clearSelectedClient() {
    document.getElementById('jform_partner_id').value = '';
    document.getElementById('selected_client').innerHTML = '';
}

// Auto-search on typing
document.addEventListener('DOMContentLoaded', function() {
    const clientSearchInput = document.getElementById('client_search');
    if (clientSearchInput) {
        clientSearchInput.addEventListener('input', function() {
            clearTimeout(clientSearchTimeout);
            clientSearchTimeout = setTimeout(searchClients, 300);
        });
    }
});

// Hide results when clicking outside
document.addEventListener('click', function(e) {
    if (!e.target.closest('.client-selector')) {
        const resultsDiv = document.getElementById('client_results');
        if (resultsDiv) {
            resultsDiv.style.display = 'none';
        }
    }
});

// Quote lines functionality
function loadQuoteLines() {
    const quoteId = <?php echo (int)($this->item->id ?? 0); ?>;
    
    if (quoteId <= 0) {
        // For new quotes, show local lines
        displayQuoteLines(quoteLines);
        return;
    }
    
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
            displayQuoteLines(data.lines);
        }
    });
}

function displayQuoteLines(lines) {
    const container = document.getElementById('quote_lines_container');
    
    if (lines.length === 0) {
        const quoteId = <?php echo (int)($this->item->id ?? 0); ?>;
        if (quoteId <= 0) {
            container.innerHTML = '<div class="alert alert-info"><i class="fas fa-info-circle"></i> Agregue líneas a su cotización usando el botón "Agregar Línea"</div>';
        } else {
            container.innerHTML = '<div class="alert alert-info"><i class="fas fa-info-circle"></i> No hay líneas en esta cotización</div>';
        }
        return;
    }
    
    let html = '<div class="table-responsive"><table class="table table-striped quote-lines-table"><thead class="table-success">';
    html += '<tr><th>Producto</th><th>Descripción</th><th>Cantidad</th><th>Precio Unit.</th><th>Subtotal</th><th>Acciones</th></tr></thead><tbody>';
    
    lines.forEach(line => {
        const lineId = line.id || ('temp_' + index);
        const productId = line.product_id || nextProductNumber;
        const quantity = parseFloat(line.product_uom_qty || line.quantity || 1);
        const price = parseFloat(line.price_unit || line.price || 0);
        const subtotal = quantity * price;
        
        html += `<tr>
            <td><span class="product-badge">${productId}</span></td>
            <td>${line.name || line.description}</td>
            <td>${quantity.toFixed(2)}</td>
            <td class="currency-amount">Q ${price.toFixed(2)}</td>
            <td><strong class="currency-amount">Q ${subtotal.toFixed(2)}</strong></td>
            <td>
                <button type="button" class="btn btn-sm btn-outline-danger" onclick="deleteQuoteLine('${lineId}')">
                    <i class="fas fa-trash"></i>
                </button>
            </td>
        </tr>`;
    });
    
    html += '</tbody></table></div>';
    container.innerHTML = html;
    
    // Update hidden field with lines data for new quotes
    const quoteId = <?php echo (int)($this->item->id ?? 0); ?>;
    if (quoteId <= 0) {
        document.getElementById('quote_lines_data').value = JSON.stringify(quoteLines);
    }
}

function addQuoteLine() {
    document.getElementById('add_line_form').style.display = 'block';
    document.getElementById('line_description').focus();
}

function cancelAddLine() {
    document.getElementById('add_line_form').style.display = 'none';
    document.getElementById('line_description').value = '';
    document.getElementById('line_quantity').value = '1.00';
    document.getElementById('line_price').value = '0.00';
}

function saveQuoteLine() {
    const quoteId = <?php echo (int)($this->item->id ?? 0); ?>;
    const description = document.getElementById('line_description').value;
    const quantity = document.getElementById('line_quantity').value;
    const price = document.getElementById('line_price').value;
    
    if (!description.trim()) {
        alert('La descripción es requerida');
        return;
    }
    
    if (quoteId <= 0) {
        // For new quotes, add to local array
        const newLine = {
            id: 'temp_' + Date.now(),
            product_id: nextProductNumber,
            description: description,
            quantity: quantity,
            price: price,
            name: description
        };
        
        quoteLines.push(newLine);
        nextProductNumber++;
        
        cancelAddLine();
        displayQuoteLines(quoteLines);
    } else {
        // For existing quotes, save to server
        fetch('<?php echo Route::_('index.php?option=com_cotizaciones&task=cotizacion.addLine'); ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `quote_id=${quoteId}&description=${encodeURIComponent(description)}&quantity=${quantity}&price=${price}&<?php echo Session::getFormToken(); ?>=1`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                cancelAddLine();
                loadQuoteLines();
            } else {
                alert('Error al agregar la línea: ' + (data.message || 'Error desconocido'));
            }
        });
    }
}

function deleteQuoteLine(lineId) {
    if (!confirm('¿Eliminar esta línea de la cotización?')) return;
    
    const quoteId = <?php echo (int)($this->item->id ?? 0); ?>;
    
    if (quoteId <= 0) {
        // For new quotes, remove from local array
        quoteLines = quoteLines.filter(line => line.id !== lineId);
        displayQuoteLines(quoteLines);
    } else {
        // For existing quotes, delete from server
        fetch('<?php echo Route::_('index.php?option=com_cotizaciones&task=cotizacion.deleteLine'); ?>', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `line_id=${lineId}&<?php echo Session::getFormToken(); ?>=1`
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                loadQuoteLines();
            } else {
                alert('Error al eliminar la línea');
            }
        });
    }
}

// Load quote lines on page load
document.addEventListener('DOMContentLoaded', function() {
    loadQuoteLines();
        Joomla.submitform(task, document.getElementById('adminForm'));
    } else {
        alert('Por favor complete todos los campos requeridos.');
    }
    
    // Load quote lines on page load
    loadQuoteLines();
};
</script>