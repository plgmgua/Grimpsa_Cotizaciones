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

// Get application instance safely
$app = Factory::getApplication();

// Safe function to get object property
function safeGet($obj, $property, $default = '') {
    if (is_object($obj) && property_exists($obj, $property)) {
        $value = $obj->$property;
        return ($value !== null && $value !== '') ? $value : $default;
    }
    return $default;
}

$isNew = (safeGet($this->item, 'id', 0) == 0);
$user = Factory::getUser();

// Get current action from URL
$currentTask = Factory::getApplication()->input->get('task', '');
$editLineId = Factory::getApplication()->input->getInt('edit_line_id', 0);
?>

<div class="cotizaciones-component">
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center">
            <div>
                <h1>
                    <?php if ($isNew): ?>
                        Nueva Cotización
                    <?php else: ?>
                        <?php echo htmlspecialchars(safeGet($this->item, 'name', 'Cotización')); ?>
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
                            <?php echo $isNew ? 'Nueva Cotización' : htmlspecialchars(safeGet($this->item, 'name', 'Cotización')); ?>
                        </li>
                    </ol>
                </nav>
            </div>
        </div>
    </div>

    <!-- Quote Header Form -->
    <form action="<?php echo Route::_('index.php?option=com_cotizaciones&view=cotizacion&layout=edit&id=' . (int) safeGet($this->item, 'id', 0)); ?>" 
          method="post" name="quoteForm" id="quoteForm" class="form-validate">
        
        <div class="quote-form-container">
            <div class="row">
                <!-- Basic Information - Full Width -->
                <div class="col-12">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="card-title mb-0">Información Básica</h5>
                        </div>
                        <div class="card-body">
                            <!-- First Row: All basic fields -->
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <?php if ($isNew): ?>
                                            <label for="client_search" class="form-label">
                                                Cliente *
                                            </label>
                                            
                                            <?php 
                                            // Clear client selection
                                            if ($app && $app->input->post->get('clear_client')) {
                                                // Redirect to clear the POST data
                                                $app->redirect(Route::_('index.php?option=com_cotizaciones&view=cotizacion&layout=edit&id=0'));
                                                return;
                                            }
                                            
                                            // Get search term and clients
                                            $clientSearch = $app ? $app->input->getString('client_search', '') : '';
                                            $selectedClientId = $app ? $app->input->getInt('selected_client_id', 0) : 0;
                                            $clients = array();
                                            $selectedClient = null;
                                            
                                            // If we have a selected client, get its info
                                            if ($selectedClientId > 0) {
                                                try {
                                                    $helper = new \Grimpsa\Component\Cotizaciones\Site\Helper\OdooHelper();
                                                    $clientInfo = $helper->getClientById($selectedClientId);
                                                    if ($clientInfo && is_array($clientInfo)) {
                                                        $selectedClient = $clientInfo;
                                                    }
                                                } catch (Exception $e) {
                                                    Factory::getApplication()->enqueueMessage('Error loading selected client: ' . $e->getMessage(), 'warning');
                                                }
                                            }
                                            
                                            // If we have a search term, get matching clients
                                            if (!empty($clientSearch) && strlen($clientSearch) >= 2) {
                                                try {
                                                    $helper = new \Grimpsa\Component\Cotizaciones\Site\Helper\OdooHelper();
                                                    $clients = $helper->getClients($clientSearch, $user->name);
                                                    
                                                    // Ensure clients is an array
                                                    if (!is_array($clients)) {
                                                        $clients = array();
                                                    }
                                                } catch (Exception $e) {
                                                    Factory::getApplication()->enqueueMessage('Error searching clients: ' . $e->getMessage(), 'warning');
                                                    $clients = array();
                                                }
                                            }
                                            ?>
                                            
                                            <?php if ($selectedClient): ?>
                                                <!-- Selected Client Display -->
                                                <div class="alert alert-success">
                                                    <strong>Cliente seleccionado:</strong> <?php echo htmlspecialchars(isset($selectedClient['name']) ? $selectedClient['name'] : 'Cliente desconocido'); ?>
                                                    <form method="post" style="display: inline;" class="ms-2">
                                                        <button type="submit" name="clear_client" value="1" class="btn btn-sm btn-outline-secondary">
                                                            Cambiar Cliente
                                                        </button>
                                                        <?php echo HTMLHelper::_('form.token'); ?>
                                                    </form>
                                                </div>
                                                <input type="hidden" name="jform[partner_id]" value="<?php echo isset($selectedClient['id']) ? (int)$selectedClient['id'] : 0; ?>" required />
                                            <?php else: ?>
                                                <!-- Client Search Form -->
                                                <form method="post" class="mb-2">
                                                    <div class="input-group">
                                                        <input type="text" name="client_search" class="form-control" 
                                                               value="<?php echo htmlspecialchars($clientSearch); ?>"
                                                               placeholder="Buscar cliente por nombre..." 
                                                               minlength="2" />
                                                        <button type="submit" class="btn btn-outline-primary">
                                                            <i class="fas fa-search"></i> Buscar
                                                        </button>
                                                        <?php if (!empty($clientSearch)): ?>
                                                            <button type="submit" name="clear_search" value="1" class="btn btn-outline-secondary">
                                                                <i class="fas fa-times"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                    <?php echo HTMLHelper::_('form.token'); ?>
                                                </form>
                                                
                                                <?php if (!empty($clientSearch)): ?>
                                                    <?php if (empty($clients)): ?>
                                                        <div class="alert alert-warning">
                                                            <i class="fas fa-exclamation-triangle"></i>
                                                            No se encontraron clientes con el término "<?php echo htmlspecialchars($clientSearch); ?>"
                                                        </div>
                                                    <?php else: ?>
                                                        <div class="card">
                                                            <div class="card-header">
                                                                <small>Resultados de búsqueda (<?php echo count($clients); ?> encontrados):</small>
                                                            </div>
                                                            <div class="card-body p-0">
                                                                <div class="list-group list-group-flush">
                                                                    <?php foreach ($clients as $client): ?>
                                                                        <?php if (!is_array($client) || !isset($client['id']) || !isset($client['name'])) continue; ?>
                                                                        <form method="post" style="display: inline;">
                                                                            <button type="submit" name="selected_client_id" value="<?php echo (int)$client['id']; ?>" 
                                                                                    class="list-group-item list-group-item-action text-start">
                                                                                <strong><?php echo htmlspecialchars($client['name']); ?></strong>
                                                                                <?php if (!empty($client['email'])): ?>
                                                                                    <br><small class="text-muted"><?php echo htmlspecialchars($client['email']); ?></small>
                                                                                <?php endif; ?>
                                                                            </button>
                                                                            <?php echo HTMLHelper::_('form.token'); ?>
                                                                        </form>
                                                                    <?php endforeach; ?>
                                                                </div>
                                                            </div>
                                                        </div>
                                                    <?php endif; ?>
                                                <?php endif; ?>
                                                
                                                <!-- Hidden field for form validation -->
                                                <input type="hidden" name="jform[partner_id]" value="" />
                                            <?php endif; ?>
                                            
                                            <small class="form-text text-muted">
                                                <?php if (!$selectedClient): ?>
                                                    Busque entre sus clientes asignados (mínimo 2 caracteres)
                                                <?php endif; ?>
                                            </small>
                                        <?php else: ?>
                                            <label for="jform_client_name" class="form-label">
                                                Cliente
                                            </label>
                                            <input type="text" id="jform_client_name" 
                                                   value="<?php echo htmlspecialchars(safeGet($this->item, 'contact_name', 'Cliente no disponible')); ?>" 
                                                   class="form-control" readonly />
                                            <input type="hidden" name="jform[partner_id]" 
                                                   value="<?php echo htmlspecialchars(safeGet($this->item, 'partner_id', '')); ?>" />
                                            <small class="form-text text-muted">El cliente no se puede cambiar una vez creada la cotización</small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="jform_date_order" class="form-label">
                                            Fecha de Cotización *
                                        </label>
                                        <input type="date" name="jform[date_order]" id="jform_date_order" 
                                               value="<?php echo date('Y-m-d', strtotime(safeGet($this->item, 'date_order', date('Y-m-d')))); ?>" 
                                               class="form-control required" required />
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="mb-3">
                                        <label for="jform_name" class="form-label">
                                            Número de Cotización
                                        </label>
                                        <input type="text" name="jform[name]" id="jform_name" 
                                               value="<?php echo htmlspecialchars(safeGet($this->item, 'name', '')); ?>" 
                                               class="form-control" readonly />
                                        <small class="form-text text-muted">
                                            <?php if (empty(safeGet($this->item, 'name', ''))): ?>
                                                Se generará automáticamente al guardar
                                            <?php else: ?>
                                                Generado automáticamente por Odoo
                                            <?php endif; ?>
                                        </small>
                                    </div>
                                </div>
                            </div>

                            <!-- Second Row: Notes -->
                            <div class="row">
                                <div class="col-md-12">
                                    <div class="mb-3">
                                        <label for="jform_note" class="form-label">
                                            Notas
                                        </label>
                                        <textarea name="jform[note]" id="jform_note" 
                                                 class="form-control" rows="4"><?php echo htmlspecialchars(safeGet($this->item, 'note', '')); ?></textarea>
                                    </div>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Header Save Actions -->
                        <div class="card-footer bg-light">
                            <div class="btn-group" role="group">
                                <?php if ($isNew): ?>
                                    <button type="submit" name="task" value="cotizacion.save" class="btn btn-success">
                                        <i class="fas fa-save"></i> Crear Cotización
                                    </button>
                                <?php else: ?>
                                    <button type="submit" name="task" value="cotizacion.save" class="btn btn-success">
                                        <i class="fas fa-save"></i> Guardar Información Básica
                                    </button>
                                    <a href="<?php echo Route::_('index.php?option=com_cotizaciones&view=cotizacion&layout=edit&id=' . (int)safeGet($this->item, 'id', 0)); ?>" 
                                       class="btn btn-info">
                                        <i class="fas fa-sync-alt"></i> Actualizar
                                    </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Hidden fields -->
            <input type="hidden" name="id" value="<?php echo (int) safeGet($this->item, 'id', 0); ?>" />
            <?php echo HTMLHelper::_('form.token'); ?>
        </div>
    </form>

    <!-- Quote Lines Section - Only show if quote has been created -->
    <?php if (safeGet($this->item, 'id', 0) > 0 && !empty(safeGet($this->item, 'name', ''))): ?>
        <?php
        // Load existing quote lines
        $model = $this->getModel();
        $existingLines = $model->getQuoteLines(safeGet($this->item, 'id', 0));
        ?>
        
        <div class="row mt-4">
            <div class="col-12">
                <div class="card">
                    <div class="card-header">
                        <h5 class="card-title mb-0">
                            <i class="fas fa-list"></i> Líneas de Cotización
                        </h5>
                    </div>
                    <div class="card-body">
                        
                        <!-- Quote Lines Table -->
                        <div class="quote-lines-table">
                            <table class="table table-striped">
                                <thead class="table-success">
                                    <tr>
                                        <th width="35%">Producto</th>
                                        <th width="15%">Cantidad</th>
                                        <th width="15%">Precio Unit.</th>
                                        <th width="15%">Subtotal</th>
                                        <th width="20%">Acciones</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($existingLines)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center text-muted">
                                            <i class="fas fa-info-circle"></i> 
                                            No hay líneas agregadas. Use el formulario de arriba para agregar productos.
                                        </td>
                                    </tr>
                                    <?php else: ?>
                                        <?php 
                                        $total = 0;
                                        foreach ($existingLines as $line): 
                                            $subtotal = $line['product_uom_qty'] * $line['price_unit'];
                                            $total += $subtotal;
                                            
                                            // Check if this line is being edited
                                            $isEditing = ($editLineId == $line['id']);
                                        ?>
                                        <tr>
                                            <?php if ($isEditing): ?>
                                                <!-- Edit Mode -->
                                                <form action="<?php echo Route::_('index.php?option=com_cotizaciones&view=cotizacion&layout=edit&id=' . (int)$this->item->id); ?>" 
                                                      method="post" style="display: contents;">
                                                    <td>
                                                        <strong><?php echo htmlspecialchars($line['product_name']); ?></strong>
                                                        <br>
                                                        <textarea name="edit_description" class="form-control form-control-sm" rows="2" 
                                                                  required><?php echo htmlspecialchars($line['name']); ?></textarea>
                                                    </td>
                                                    <td>
                                                        <input type="number" name="edit_quantity" class="form-control form-control-sm" 
                                                               value="<?php echo $line['product_uom_qty']; ?>" min="1" step="1" required />
                                                    </td>
                                                    <td>
                                                        <div class="input-group input-group-sm">
                                                            <span class="input-group-text">Q</span>
                                                            <input type="number" name="edit_price" class="form-control" 
                                                                   value="<?php echo $line['price_unit']; ?>" step="0.01" min="0" required />
                                                        </div>
                                                    </td>
                                                    <td class="currency-amount">Q <?php echo number_format($subtotal, 2); ?></td>
                                                    <td>
                                                        <div class="btn-group btn-group-sm">
                                                            <button type="submit" name="task" value="cotizacion.updateLine" class="btn btn-success" title="Guardar cambios">
                                                                <i class="fas fa-save"></i>
                                                            </button>
                                                            <a href="<?php echo Route::_('index.php?option=com_cotizaciones&view=cotizacion&layout=edit&id=' . (int)$this->item->id); ?>" 
                                                               class="btn btn-secondary" title="Cancelar">
                                                                <i class="fas fa-times"></i>
                                                            </a>
                                                        </div>
                                                    </td>
                                                    <input type="hidden" name="line_id" value="<?php echo $line['id']; ?>" />
                                                    <?php echo HTMLHelper::_('form.token'); ?>
                                                </form>
                                            <?php else: ?>
                                                <!-- View Mode -->
                                                <td>
                                                    <strong><?php echo htmlspecialchars($line['product_name']); ?></strong>
                                                    <br><small class="text-muted"><?php echo htmlspecialchars($line['name']); ?></small>
                                                </td>
                                                <td><?php echo number_format($line['product_uom_qty'], 0); ?></td>
                                                <td>Q <?php echo number_format($line['price_unit'], 2); ?></td>
                                                <td class="currency-amount">Q <?php echo number_format($subtotal, 2); ?></td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="<?php echo Route::_('index.php?option=com_cotizaciones&view=cotizacion&layout=edit&id=' . (int)$this->item->id . '&edit_line_id=' . $line['id']); ?>" 
                                                           class="btn btn-outline-primary" title="Editar línea">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <form action="<?php echo Route::_('index.php?option=com_cotizaciones&view=cotizacion&layout=edit&id=' . (int)$this->item->id); ?>" 
                                                              method="post" style="display: inline;" 
                                                              onsubmit="return confirm('¿Está seguro de que desea eliminar esta línea?');">
                                                            <button type="submit" name="task" value="cotizacion.deleteLine" class="btn btn-outline-danger" title="Eliminar línea">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                            <input type="hidden" name="line_id" value="<?php echo $line['id']; ?>" />
                                                            <?php echo HTMLHelper::_('form.token'); ?>
                                                        </form>
                                                    </div>
                                                </td>
                                            <?php endif; ?>
                                        </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="table-light">
                                        <th colspan="3" class="text-end">Total:</th>
                                        <th class="currency-amount">Q <?php echo number_format($total ?? 0, 2); ?></th>
                                        <th></th>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                        
                        <!-- Add Product Line Form - Moved to bottom -->
                        <form action="<?php echo Route::_('index.php?option=com_cotizaciones&view=cotizacion&layout=edit&id=' . (int)$this->item->id); ?>" 
                              method="post" class="add-line-form mt-4">
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
                                                <label for="line_description" class="form-label">Descripción del Producto *</label>
                                                <textarea name="line_description" id="line_description" class="form-control" rows="3" 
                                                          placeholder="Descripción detallada del producto o servicio..." required></textarea>
                                                <small class="form-text text-muted">
                                                    Incluye especificaciones, materiales, dimensiones, etc.
                                                </small>
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="mb-3">
                                                <label for="line_quantity" class="form-label">Cantidad *</label>
                                                <input type="number" name="line_quantity" id="line_quantity" class="form-control" 
                                                       value="1" min="1" step="1" required />
                                            </div>
                                        </div>
                                        <div class="col-md-3">
                                            <div class="mb-3">
                                                <label for="line_price" class="form-label">Precio Unitario *</label>
                                                <div class="input-group">
                                                    <span class="input-group-text">Q</span>
                                                    <input type="number" name="line_price" id="line_price" class="form-control" 
                                                           step="0.01" min="0" placeholder="0.00" required />
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                    <div class="text-end">
                                        <button type="submit" name="task" value="cotizacion.addLine" class="btn btn-success">
                                            <i class="fas fa-plus"></i> Agregar Línea
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" name="quote_id" value="<?php echo (int)$this->item->id; ?>" />
                            <?php echo HTMLHelper::_('form.token'); ?>
                        </form>
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
    <div class="form-actions mt-4">
        <div class="btn-toolbar" role="toolbar">
            <div class="btn-group me-2" role="group">
                <a href="<?php echo Route::_('index.php?option=com_cotizaciones&view=cotizaciones'); ?>" class="btn btn-secondary">
                    <i class="fas fa-arrow-left"></i> Volver a Lista
                </a>
            </div>
        </div>
    </div>
</div>
