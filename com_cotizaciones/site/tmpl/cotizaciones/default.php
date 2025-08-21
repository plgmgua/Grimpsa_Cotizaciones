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

$user = Factory::getUser();
$app = Factory::getApplication();

// Safe defaults
$items = isset($this->items) && is_array($this->items) ? $this->items : [];
$state = isset($this->state) ? $this->state : null;

// Get filter values
$currentSearch = '';
$currentState = '';
$currentLimit = 20;

if ($state) {
    $currentSearch = $state->get('filter.search', '');
    $currentState = $state->get('filter.state', '');
    $currentLimit = $state->get('list.limit', 20);
}

$hasSearch = !empty($currentSearch);
$hasStateFilter = !empty($currentState);

// State mappings
$stateLabels = [
    'draft' => 'Borrador',
    'sent' => 'Enviada', 
    'sale' => 'Confirmada',
    'done' => 'Completada',
    'cancel' => 'Cancelada'
];

$stateBadges = [
    'draft' => 'bg-secondary',
    'sent' => 'bg-info',
    'sale' => 'bg-success', 
    'done' => 'bg-primary',
    'cancel' => 'bg-danger'
];
?>

<div class="cotizaciones-component">
    <!-- Skip link for accessibility -->
    <a href="#main-content" class="skip-link">Saltar al contenido principal</a>
    
    <div class="page-header">
        <div class="d-flex justify-content-between align-items-center">
            <h1>Mis Cotizaciones</h1>
            <div class="user-info">
                <small class="text-muted">
                    Agente de Ventas: <?php echo htmlspecialchars($user->name); ?>
                </small>
            </div>
        </div>
    </div>

    <!-- Search and Filters -->
    <div class="quotes-ribbon">
        <div class="row">
            <div class="col-md-4">
                <form action="<?php echo Route::_('index.php?option=com_cotizaciones&view=cotizaciones'); ?>" method="post">
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text" name="filter_search" 
                               value="<?php echo htmlspecialchars($currentSearch); ?>" 
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
                    <input type="hidden" name="filter_state" value="<?php echo htmlspecialchars($currentState); ?>" />
                    <input type="hidden" name="task" value="" />
                    <?php echo HTMLHelper::_('form.token'); ?>
                </form>
            </div>
            <div class="col-md-2">
                <form action="<?php echo Route::_('index.php?option=com_cotizaciones&view=cotizaciones'); ?>" method="post">
                    <select name="filter_state" class="form-select" onchange="this.form.submit()">
                        <option value="">Todos los Estados</option>
                        <option value="draft"<?php echo ($currentState == 'draft') ? ' selected' : ''; ?>>Borrador</option>
                        <option value="sent"<?php echo ($currentState == 'sent') ? ' selected' : ''; ?>>Enviada</option>
                        <option value="sale"<?php echo ($currentState == 'sale') ? ' selected' : ''; ?>>Confirmada</option>
                        <option value="done"<?php echo ($currentState == 'done') ? ' selected' : ''; ?>>Completada</option>
                        <option value="cancel"<?php echo ($currentState == 'cancel') ? ' selected' : ''; ?>>Cancelada</option>
                    </select>
                    <input type="hidden" name="task" value="" />
                    <input type="hidden" name="filter_search" value="<?php echo htmlspecialchars($currentSearch); ?>" />
                    <?php echo HTMLHelper::_('form.token'); ?>
                </form>
            </div>
            <div class="col-md-2">
                <form action="<?php echo Route::_('index.php?option=com_cotizaciones&view=cotizaciones'); ?>" method="post">
                    <select name="limit" class="form-select" onchange="this.form.submit()">
                        <option value="20"<?php echo ($currentLimit == 20) ? ' selected' : ''; ?>>20 por página</option>
                        <option value="30"<?php echo ($currentLimit == 30) ? ' selected' : ''; ?>>30 por página</option>
                        <option value="50"<?php echo ($currentLimit == 50) ? ' selected' : ''; ?>>50 por página</option>
                    </select>
                    <input type="hidden" name="task" value="" />
                    <input type="hidden" name="filter_search" value="<?php echo htmlspecialchars($currentSearch); ?>" />
                    <input type="hidden" name="filter_state" value="<?php echo htmlspecialchars($currentState); ?>" />
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
                            <strong>Búsqueda:</strong> "<?php echo htmlspecialchars($currentSearch); ?>"
                        <?php endif; ?>
                        <?php if ($hasStateFilter): ?>
                            <?php if ($hasSearch): ?> | <?php endif; ?>
                            <strong>Estado:</strong> <?php echo isset($stateLabels[$currentState]) ? $stateLabels[$currentState] : $currentState; ?>
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
    <div id="main-content" class="quotes-table-container">
        <?php if (empty($items)): ?>
            <?php if ($hasSearch || $hasStateFilter): ?>
                <div class="alert alert-warning">
                    <h4><i class="fas fa-search"></i> No se Encontraron Resultados</h4>
                    <p>No se encontraron cotizaciones que coincidan con los filtros aplicados.</p>
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
                    <?php foreach ($items as $i => $item): ?>
                        <?php if (!is_array($item)) continue; ?>
                        <?php
                        $itemId = isset($item['id']) ? (int) $item['id'] : 0;
                        $itemName = isset($item['name']) ? $item['name'] : 'Sin número';
                        $contactName = isset($item['contact_name']) ? $item['contact_name'] : '';
                        $dateOrder = isset($item['date_order']) ? $item['date_order'] : '';
                        $amountTotal = isset($item['amount_total']) ? $item['amount_total'] : '0.00';
                        $itemState = isset($item['state']) ? $item['state'] : 'draft';
                        
                        $stateLabel = isset($stateLabels[$itemState]) ? $stateLabels[$itemState] : 'Borrador';
                        $badgeClass = isset($stateBadges[$itemState]) ? $stateBadges[$itemState] : 'bg-secondary';
                        ?>
                        <tr>
                            <td>
                                <div class="quote-name">
                                    <strong>
                                        <a href="<?php echo Route::_('index.php?option=com_cotizaciones&view=cotizacion&layout=edit&id=' . $itemId); ?>" 
                                           class="text-decoration-none text-dark">
                                            <?php echo htmlspecialchars($itemName); ?>
                                        </a>
                                    </strong>
                                </div>
                            </td>
                            <td>
                                <?php if (!empty($contactName) && $contactName !== 'Cliente no disponible'): ?>
                                    <strong><?php echo htmlspecialchars($contactName); ?></strong>
                                <?php else: ?>
                                    <span class="text-muted">Cliente no disponible</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($dateOrder) && $dateOrder !== '0000-00-00'): ?>
                                    <span class="date"><?php echo htmlspecialchars(date('d/m/Y', strtotime($dateOrder))); ?></span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if (!empty($amountTotal) && $amountTotal !== '0.00' && is_numeric($amountTotal)): ?>
                                    <span class="currency">Q <?php echo number_format((float) $amountTotal, 2); ?></span>
                                <?php else: ?>
                                    <span class="text-muted">Q 0.00</span>
                                <?php endif; ?>
                            </td>
                            <td>
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