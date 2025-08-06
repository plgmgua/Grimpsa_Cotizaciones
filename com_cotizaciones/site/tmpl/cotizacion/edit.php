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
                                        <label for="jform_partner_id" class="form-label">
                                            Cliente *
                                        </label>
                                        <input type="text" name="jform[partner_id]" id="jform_partner_id" 
                                               value="<?php echo htmlspecialchars($this->item->partner_id ?? ''); ?>" 
                                               class="form-control required" required 
                                               placeholder="ID del cliente" />
                                        <small class="form-text text-muted">Ingrese el ID del cliente (requerido para generar la cotización)</small>
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
                                                           placeholder="Ej: Tarjetas de presentación" required />
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
                                                              placeholder="Descripción detallada del producto o servicio..."></textarea>
                                                    <small class="form-text text-muted">
                                                        Incluye especificaciones, materiales, dimensiones, etc.
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
        alert('Debe ingresar un ID de cliente');
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

function addQuoteLine() {
    const name = document.getElementById('product_name').value.trim();
    const quantity = parseFloat(document.getElementById('product_quantity').value) || 1;
    const price = parseFloat(document.getElementById('product_price').value) || 0;
    const description = document.getElementById('product_description').value.trim();
    
    // Validation
    if (!name) {
        alert('Por favor ingrese el nombre del producto');
        document.getElementById('product_name').focus();
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
    
    // Create line object
    const line = {
        id: ++lineCounter,
        name: name,
        quantity: quantity,
        price: price,
        description: description,
        subtotal: quantity * price
    };
    
    // Add to array
    quoteLines.push(line);
    
    // Update table
    updateQuoteLinesTable();
    
    // Clear form
    clearProductForm();
    
    // Focus back to product name
    document.getElementById('product_name').focus();
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
                    ${line.description ? '<br><small class="text-muted">' + escapeHtml(line.description) + '</small>' : ''}
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
    document.getElementById('product_name').value = '';
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