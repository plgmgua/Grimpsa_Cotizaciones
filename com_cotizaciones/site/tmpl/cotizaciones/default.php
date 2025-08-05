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
use Joomla\CMS\Session\Session;
use Joomla\CMS\Factory;

HTMLHelper::_('bootstrap.framework');

$user = Factory::getUser();

// Safe function to escape strings
function safeEscape($value, $default = '') {
    if (is_string($value) && !empty($value)) {
        return htmlspecialchars($value, ENT_QUOTES, 'UTF-8');
    }
    return $default;
}

// Safe function to get array value
function safeGet($array, $key, $default = '') {
    if (is_array($array) && isset($array[$key])) {
        return $array[$key];
    }
    return $default;
}

// Function to get state badge class
function getStateBadgeClass($state) {
    switch($state) {
        case 'draft': return 'bg-secondary';
        case 'sent': return 'bg-info';
        case 'sale': return 'bg-success';
        case 'done': return 'bg-primary';
        case 'cancel': return 'bg-danger';
        default: return 'bg-secondary';
    }
}

// Function to get state label
function getStateLabel($state) {
    switch($state) {
        case 'draft': return 'Borrador';
        case 'sent': return 'Enviada';
        case 'sale': return 'Confirmada';
        case 'done': return 'Completada';
        case 'cancel': return 'Cancelada';
        default: return 'Borrador';
    }
}
?>

<div class="cotizaciones-component">
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center">
            <h1>Mis Cotizaciones</h1>
            <div class="user-info">
                <small class="text-muted">
                    Agente de Ventas: <?php echo safeEscape($user->name); ?>
                </small>
            </div>
        </div>
    </div>

    <!-- Main Actions Ribbon -->
    <div class="quotes-ribbon">
        <div class="row">
            <div class="col-md-8">
                <form action="<?php echo Route::_('index.php?option=com_cotizaciones&view=cotizaciones'); ?>" method="post" name="adminForm" id="adminForm">
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text" name="filter_search" id="filter_search" 
                               value="" 
                               class="form-control" 
                               placeholder="Buscar por número, cliente..." />
                        <button class="btn btn-outline-secondary" type="submit">
                            Buscar
                        </button>
                    </div>
                    <input type="hidden" name="task" value="" />
                    <?php echo HTMLHelper::_('form.token'); ?>
                </form>
            </div>
            <div class="col-md-4 text-end">
                <div class="btn-group" role="group">
                    <a href="<?php echo Route::_('index.php?option=com_cotizaciones&view=cotizacion&layout=edit&id=0'); ?>" 
                       class="btn btn-success btn-lg">
                        <i class="fas fa-plus"></i> Nueva Cotización
                    </a>
                    <button type="button" class="btn btn-info btn-lg" onclick="window.location.reload()">
                        <i class="fas fa-sync-alt"></i> Actualizar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Quotes Table -->
    <div class="quotes-table-container">
        <?php if (empty($this->items) || !is_array($this->items)): ?>
            <div class="alert alert-info">
                <h4><i class="fas fa-info-circle"></i> No se Encontraron Cotizaciones</h4>
                <p>Aún no tienes cotizaciones. Crea tu primera cotización para comenzar.</p>
                <a href="<?php echo Route::_('index.php?option=com_cotizaciones&view=cotizacion&layout=edit'); ?>" 
                   class="btn btn-primary">
                    Crear Tu Primera Cotización
                </a>
            </div>
        <?php else: ?>
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th width="5%" class="text-center">ID</th>
                        <th width="20%">Número</th>
                        <th width="25%">Cliente</th>
                        <th width="15%">Fecha</th>
                        <th width="15%">Total</th>
                        <th width="10%">Estado</th>
                        <th width="10%" class="text-center">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($this->items as $i => $item): ?>
                        <?php if (!is_array($item)) continue; ?>
                        <tr>
                            <td class="text-center">
                                <span class="badge bg-secondary"><?php echo (int)safeGet($item, 'id', 0); ?></span>
                            </td>
                            <td>
                                <div class="quote-name">
                                    <strong>
                                        <a href="<?php echo Route::_('index.php?option=com_cotizaciones&view=cotizacion&layout=edit&id=' . (int)safeGet($item, 'id', 0)); ?>" 
                                           class="text-decoration-none text-dark">
                                            <?php echo safeEscape(safeGet($item, 'name'), 'Sin número'); ?>
                                        </a>
                                    </strong>
                                </div>
                            </td>
                            <td>
                                <?php 
                                $contactName = safeGet($item, 'contact_name');
                                if (!empty($contactName)): 
                                ?>
                                    <strong><?php echo safeEscape($contactName); ?></strong>
                                    <br><small class="text-muted">ID: <?php echo safeEscape(safeGet($item, 'partner_id')); ?></small>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                $dateOrder = safeGet($item, 'date_order');
                                if (!empty($dateOrder)): 
                                ?>
                                    <span class="date"><?php echo safeEscape(date('d/m/Y', strtotime($dateOrder))); ?></span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                $total = safeGet($item, 'amount_total');
                                if (!empty($total) && $total !== '0.00'): 
                                ?>
                                    <span class="currency">Q <?php echo number_format((float)$total, 2); ?></span>
                                <?php else: ?>
                                    <span class="text-muted">Q 0.00</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                $state = safeGet($item, 'state', 'draft');
                                $badgeClass = getStateBadgeClass($state);
                                $stateLabel = getStateLabel($state);
                                ?>
                                <span class="badge <?php echo $badgeClass; ?>"><?php echo $stateLabel; ?></span>
                            </td>
                            <td>
                                <div class="btn-group btn-group-sm" role="group">
                                    <!-- Edit Quote Button -->
                                    <a href="<?php echo Route::_('index.php?option=com_cotizaciones&view=cotizacion&layout=edit&id=' . (int)safeGet($item, 'id', 0)); ?>" 
                                       class="btn btn-outline-primary btn-sm" 
                                       title="Editar Cotización">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    
                                    <!-- Delete Button -->
                                    <button type="button" 
                                            class="btn btn-outline-danger btn-sm" 
                                            onclick="deleteQuote(<?php echo (int)safeGet($item, 'id', 0); ?>, '<?php echo addslashes(safeGet($item, 'name', 'Sin número')); ?>')" 
                                            title="Eliminar">
                                        <i class="fas fa-trash"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title" id="deleteModalLabel">Confirmar Eliminación</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>¿Estás seguro de que quieres eliminar esta cotización?</p>
                <p><strong id="deleteQuoteName"></strong></p>
                <p class="text-muted">Esta acción no se puede deshacer.</p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <form id="deleteForm" method="post" style="display: inline;">
                    <input type="hidden" name="task" value="cotizacion.delete" />
                    <input type="hidden" name="id" id="deleteQuoteId" value="" />
                    <?php echo HTMLHelper::_('form.token'); ?>
                    <button type="submit" class="btn btn-danger">Eliminar</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function deleteQuote(quoteId, quoteName) {
    document.getElementById('deleteQuoteId').value = quoteId;
    document.getElementById('deleteQuoteName').textContent = quoteName;
    
    // Initialize Bootstrap modal
    var deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    deleteModal.show();
}

// Set form action to current page
document.addEventListener('DOMContentLoaded', function() {
    var deleteForm = document.getElementById('deleteForm');
    if (deleteForm) {
        deleteForm.action = window.location.href.split('?')[0] + '?option=com_cotizaciones&view=cotizaciones';
    }
});
</script>