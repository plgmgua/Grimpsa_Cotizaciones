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

// Initialize variables safely
$app = Factory::getApplication();
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
    if (is_array($array) && array_key_exists($key, $array)) {
        return $array[$key];
    }
    return $default;
}

// Function to get state badge class
function getStateBadgeClass($state) {
    $state = trim($state);
    switch ($state) {
        case 'draft': 
            return 'bg-secondary';
        case 'sent': 
            return 'bg-info';
        case 'sale': 
            return 'bg-success';
        case 'done': 
            return 'bg-primary';
        case 'cancel': 
            return 'bg-danger';
        default: 
            return 'bg-secondary';
    }
}

// Function to get state label
function getStateLabel($state) {
    $state = trim($state);
    switch ($state) {
        case 'draft': 
            return 'Borrador';
        case 'sent': 
            return 'Enviada';
        case 'sale': 
            return 'Confirmada';
        case 'done': 
            return 'Completada';
        case 'cancel': 
            return 'Cancelada';
        default: 
            return 'Borrador';
    }
}

// Get filter values safely
$currentSearch = '';
$currentState = '';
$currentLimit = 20;

if (isset($this->state) && is_object($this->state)) {
    $currentSearch = $this->state->get('filter.search', '');
    $currentState = $this->state->get('filter.state', '');
    $currentLimit = $this->state->get('list.limit', 20);
}

$hasSearch = !empty($currentSearch);
$hasStateFilter = !empty($currentState);
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
            <div class="col-md-4">
                <form action="<?php echo Route::_('index.php?option=com_cotizaciones&view=cotizaciones'); ?>" method="post" name="adminForm" id="adminForm">
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text" name="filter_search" id="filter_search" 
                               value="<?php echo safeEscape($currentSearch); ?>" 
                               class="form-control" 
                               placeholder="Buscar por nombre de cliente..." />
                        <button class="btn btn-outline-secondary" type="submit">
                            Buscar
                        </button>
                        <?php if ($hasSearch || $hasStateFilter): ?>
                            <a href="<?php echo Route::_('index.php?option=com_cotizaciones&view=cotizaciones'); ?>" 
                               class="btn btn-outline-warning" title="Limpiar búsqueda">
                                <i class="fas fa-times"></i>
                            </a>
                        <?php endif; ?>
                    </div>
                    <input type="hidden" name="filter_state" value="<?php echo safeEscape($currentState); ?>" />
                    <input type="hidden" name="task" value="" />
                    <?php echo HTMLHelper::_('form.token'); ?>
                </form>
            </div>
            <div class="col-md-2">
                <form action="<?php echo Route::_('index.php?option=com_cotizaciones&view=cotizaciones'); ?>" method="post" name="stateForm" id="stateForm">
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-filter"></i>
                        </span>
                        <select name="filter_state" class="form-select" onchange="this.form.submit()">
                            <option value="">Todos los Estados</option>
                            <option value="draft" <?php echo ($currentState == 'draft') ? 'selected' : ''; ?>>Borrador</option>
                            <option value="sent" <?php echo ($currentState == 'sent') ? 'selected' : ''; ?>>Enviada</option>
                            <option value="sale" <?php echo ($currentState == 'sale') ? 'selected' : ''; ?>>Confirmada</option>
                            <option value="done" <?php echo ($currentState == 'done') ? 'selected' : ''; ?>>Completada</option>
                            <option value="cancel" <?php echo ($currentState == 'cancel') ? 'selected' : ''; ?>>Cancelada</option>
                        </select>
                    </div>
                    <input type="hidden" name="task" value="" />
                    <input type="hidden" name="filter_search" value="<?php echo safeEscape($currentSearch); ?>" />
                    <?php echo HTMLHelper::_('form.token'); ?>
                </form>
            </div>
            <div class="col-md-2">
                <form action="<?php echo Route::_('index.php?option=com_cotizaciones&view=cotizaciones'); ?>" method="post" name="limitForm" id="limitForm">
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-list"></i>
                        </span>
                        <select name="limit" class="form-select" onchange="this.form.submit()">
                            <option value="20" <?php echo ($currentLimit == 20) ? 'selected' : ''; ?>>20 por página</option>
                            <option value="30" <?php echo ($currentLimit == 30) ? 'selected' : ''; ?>>30 por página</option>
                            <option value="50" <?php echo ($currentLimit == 50) ? 'selected' : ''; ?>>50 por página</option>
                        </select>
                    </div>
                    <input type="hidden" name="task" value="" />
                    <input type="hidden" name="filter_search" value="<?php echo safeEscape($currentSearch); ?>" />
                    <input type="hidden" name="filter_state" value="<?php echo safeEscape($currentState); ?>" />
                    <?php echo HTMLHelper::_('form.token'); ?>
                </form>
            </div>
            <div class="col-md-4 text-end">
                <div class="btn-group" role="group">
                    <a href="<?php echo Route::_('index.php?option=com_cotizaciones&view=cotizacion&layout=edit&id=0'); ?>" 
                       class="btn btn-success btn-lg">
                        <i class="fas fa-plus"></i> Nueva Cotización
                    </a>
                    <button type="button" class="btn btn-info" onclick="window.location.reload()">
                        <i class="fas fa-sync-alt"></i> Actualizar
                    </button>
                </div>
            </div>
        </div>
        
        <?php if ($hasSearch || $hasStateFilter): ?>
            <div class="row mt-3">
                <div class="col-12">
                    <div class="alert alert-info mb-0">
                        <i class="fas fa-filter"></i> 
                        Filtros activos:
                        <?php if ($hasSearch): ?>
                            <strong>Búsqueda:</strong> "<?php echo safeEscape($currentSearch); ?>"
                        <?php endif; ?>
                        <?php if ($hasStateFilter): ?>
                            <?php if ($hasSearch): ?> | <?php endif; ?>
                            <strong>Estado:</strong> <?php echo getStateLabel($currentState); ?>
                        <?php endif; ?>
                        <a href="<?php echo Route::_('index.php?option=com_cotizaciones&view=cotizaciones'); ?>" 
                           class="btn btn-sm btn-outline-primary ms-2">
                            Limpiar Filtros
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Quotes Table -->
    <div class="quotes-table-container">
        <?php if (empty($this->items) || !is_array($this->items)): ?>
            <?php if ($hasSearch || $hasStateFilter): ?>
                <div class="alert alert-warning">
                    <h4><i class="fas fa-search"></i> No se Encontraron Resultados</h4>
                    <p>No se encontraron cotizaciones que coincidan con los filtros aplicados.</p>
                    <p>Intenta con:</p>
                    <ul>
                        <?php if ($hasSearch): ?>
                            <li>Verificar la ortografía del nombre del cliente</li>
                            <li>Usar términos de búsqueda más generales</li>
                        <?php endif; ?>
                        <?php if ($hasStateFilter): ?>
                            <li>Seleccionar un estado diferente</li>
                        <?php endif; ?>
                        <li>Limpiar todos los filtros</li>
                    </ul>
                    <a href="<?php echo Route::_('index.php?option=com_cotizaciones&view=cotizaciones'); ?>" 
                       class="btn btn-primary">
                        Ver Todas las Cotizaciones
                    </a>
                </div>
            <?php else: ?>
                <div class="alert alert-info">
                    <h4><i class="fas fa-info-circle"></i> No se Encontraron Cotizaciones</h4>
                    <p>Aún no tienes cotizaciones. Crea tu primera cotización para comenzar.</p>
                    <a href="<?php echo Route::_('index.php?option=com_cotizaciones&view=cotizacion&layout=edit'); ?>" 
                       class="btn btn-primary">
                        Crear Tu Primera Cotización
                    </a>
                </div>
            <?php endif; ?>
        <?php else: ?>
            <table class="table table-striped table-hover">
                <thead class="table-dark">
                    <tr>
                        <th width="25%">Número</th>
                        <th width="35%">Cliente</th>
                        <th width="15%">Fecha</th>
                        <th width="15%">Total</th>
                        <th width="15%">Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($this->items as $i => $item): ?>
                        <?php if (!is_array($item)) continue; ?>
                        <tr>
                            <td>
                                <div class="quote-name">
                                    <strong>
                                        <a href="<?php echo Route::_('index.php?option=com_cotizaciones&view=cotizacion&layout=edit&id=' . (int) safeGet($item, 'id', 0)); ?>" 
                                           class="text-decoration-none text-dark">
                                            <?php echo safeEscape(safeGet($item, 'name'), 'Sin número'); ?>
                                        </a>
                                    </strong>
                                </div>
                            </td>
                            <td>
                                <?php 
                                $contactName = safeGet($item, 'contact_name');
                                if (!empty($contactName) && $contactName !== 'Cliente no disponible'): 
                                ?>
                                    <strong><?php echo safeEscape($contactName); ?></strong>
                                <?php else: ?>
                                    <span class="text-muted">Cliente no disponible</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                $dateOrder = safeGet($item, 'date_order');
                                if (!empty($dateOrder) && $dateOrder !== '0000-00-00'): 
                                ?>
                                    <span class="date"><?php echo safeEscape(date('d/m/Y', strtotime($dateOrder))); ?></span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php 
                                $total = safeGet($item, 'amount_total');
                                if (!empty($total) && $total !== '0.00' && is_numeric($total)): 
                                ?>
                                    <span class="currency">Q <?php echo number_format((float) $total, 2); ?></span>
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
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <!-- Pagination -->
            <?php if (isset($this->pagination) && $this->pagination && $this->pagination->pagesTotal > 1): ?>
                <div class="d-flex justify-content-center mt-4">
                    <?php echo $this->pagination->getListFooter(); ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>