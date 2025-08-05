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
            <div class="col-md-5">
                <form action="<?php echo Route::_('index.php?option=com_cotizaciones&view=cotizaciones'); ?>" method="post" name="adminForm" id="adminForm">
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-search"></i>
                        </span>
                        <input type="text" name="filter_search" id="filter_search" 
                               value="<?php echo safeEscape($this->state->get('filter.search', '')); ?>" 
                               class="form-control" 
                               placeholder="Buscar por nombre de cliente..." />
                        <button class="btn btn-outline-secondary" type="submit">
                            Buscar
                        </button>
                        <?php if (!empty($this->state->get('filter.search', ''))): ?>
                            <a href="<?php echo Route::_('index.php?option=com_cotizaciones&view=cotizaciones'); ?>" 
                               class="btn btn-outline-warning" title="Limpiar búsqueda">
                                <i class="fas fa-times"></i>
                            </a>
                        <?php endif; ?>
                        <?php 
                        $selectedClients = $this->state->get('filter.clients', []);
                        if (!empty($selectedClients)): 
                        ?>
                            <a href="<?php echo Route::_('index.php?option=com_cotizaciones&view=cotizaciones'); ?>" 
                               class="btn btn-sm btn-outline-warning ms-2">
                                Limpiar Filtros de Cliente
                            </a>
                        <?php endif; ?>
                    </div>
                    <input type="hidden" name="task" value="" />
                    <?php echo HTMLHelper::_('form.token'); ?>
                </form>
            </div>
            <div class="col-md-3">
                <form action="<?php echo Route::_('index.php?option=com_cotizaciones&view=cotizaciones'); ?>" method="post" name="limitForm" id="limitForm">
                    <div class="input-group">
                        <span class="input-group-text">
                            <i class="fas fa-list"></i>
                        </span>
                        <select name="limit" class="form-select" onchange="this.form.submit()">
                            <option value="20" <?php echo ($this->state->get('list.limit', 20) == 20) ? 'selected' : ''; ?>>20 por página</option>
                            <option value="30" <?php echo ($this->state->get('list.limit', 20) == 30) ? 'selected' : ''; ?>>30 por página</option>
                            <option value="50" <?php echo ($this->state->get('list.limit', 20) == 50) ? 'selected' : ''; ?>>50 por página</option>
                        </select>
                    </div>
                    <input type="hidden" name="task" value="" />
                    <input type="hidden" name="filter_search" value="<?php echo safeEscape($this->state->get('filter.search', '')); ?>" />
                    <?php echo HTMLHelper::_('form.token'); ?>
                </form>
            </div>
            <div class="col-md-3 text-end">
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
        
        <?php if (!empty($this->state->get('filter.search', ''))): ?>
            <div class="row mt-3">
                <div class="col-12">
                    <div class="alert alert-info mb-0">
                        <i class="fas fa-search"></i> 
                        Mostrando resultados para: <strong>"<?php echo safeEscape($this->state->get('filter.search', '')); ?>"</strong>
                        <a href="<?php echo Route::_('index.php?option=com_cotizaciones&view=cotizaciones'); ?>" 
                           class="btn btn-sm btn-outline-primary ms-2">
                            Ver todas las cotizaciones
                        </a>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Quotes Table -->
    <div class="quotes-table-container">
        <?php if (empty($this->items) || !is_array($this->items)): ?>
            <?php if (!empty($this->state->get('filter.search', ''))): ?>
                <div class="alert alert-warning">
                    <h4><i class="fas fa-search"></i> No se Encontraron Resultados</h4>
                    <p>No se encontraron cotizaciones que coincidan con "<strong><?php echo safeEscape($this->state->get('filter.search', '')); ?></strong>".</p>
                    <p>Intenta con:</p>
                    <ul>
                        <li>Verificar la ortografía del nombre del cliente</li>
                        <li>Usar solo parte del nombre del cliente</li>
                        <li>Buscar con términos más generales</li>
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
                        <th width="35%" class="filterable-column">
                            <div class="column-header-wrapper">
                                <span>Cliente</span>
                                <button type="button" class="btn btn-sm btn-link text-white p-0 ms-2 column-filter-btn" 
                                        onclick="toggleClientFilter()" title="Filtrar por cliente">
                                    <i class="fas fa-filter"></i>
                                </button>
                            </div>
                            <!-- Client Filter Dropdown -->
                            <div id="clientFilterDropdown" class="filter-dropdown" style="display: none;">
                                <div class="filter-dropdown-header">
                                    <div class="d-flex justify-content-between align-items-center">
                                        <span class="fw-bold">Filtrar por Cliente</span>
                                        <button type="button" class="btn-close btn-close-white" onclick="closeClientFilter()"></button>
                                    </div>
                                </div>
                                <div class="filter-dropdown-body">
                                    <div class="filter-actions mb-2">
                                        <button type="button" class="btn btn-sm btn-outline-light me-1" onclick="selectAllClients()">
                                            Seleccionar Todo
                                        </button>
                                        <button type="button" class="btn btn-sm btn-outline-light" onclick="clearAllClients()">
                                            Limpiar Todo
                                        </button>
                                    </div>
                                    <div class="filter-search mb-2">
                                        <input type="text" id="clientFilterSearch" class="form-control form-control-sm" 
                                               placeholder="Buscar cliente..." onkeyup="filterClientList()">
                                    </div>
                                    <div class="filter-options" id="clientFilterOptions">
                                        <?php if (!empty($this->distinctClients) && is_array($this->distinctClients)): ?>
                                            <?php 
                                            $selectedClients = $this->state->get('filter.clients', []);
                                            foreach ($this->distinctClients as $client): 
                                                if (!is_array($client) || empty($client['id']) || empty($client['name'])) continue;
                                                $isSelected = in_array($client['id'], $selectedClients);
                                            ?>
                                                <div class="form-check client-option" data-client-name="<?php echo strtolower(safeEscape($client['name'])); ?>">
                                                    <input class="form-check-input client-checkbox" type="checkbox" 
                                                           value="<?php echo safeEscape($client['id']); ?>" 
                                                           id="client_<?php echo safeEscape($client['id']); ?>"
                                                           <?php echo $isSelected ? 'checked' : ''; ?>>
                                                    <label class="form-check-label" for="client_<?php echo safeEscape($client['id']); ?>">
                                                        <?php echo safeEscape($client['name']); ?>
                                                    </label>
                                                </div>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <div class="text-muted">No hay clientes disponibles</div>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                <div class="filter-dropdown-footer">
                                    <button type="button" class="btn btn-sm btn-success me-2" onclick="applyClientFilter()">
                                        Aplicar Filtro
                                    </button>
                                    <button type="button" class="btn btn-sm btn-secondary" onclick="closeClientFilter()">
                                        Cancelar
                                    </button>
                                </div>
                            </div>
                        </th>
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
                                <?php else: ?>
                                    <span class="text-muted">Cliente no disponible</span>
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
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
            
            <!-- Pagination -->
            <?php if ($this->pagination && $this->pagination->pagesTotal > 1): ?>
                <div class="d-flex justify-content-center mt-4">
                    <?php echo $this->pagination->getListFooter(); ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>
    </div>
</div>

<script>
// Client Filter Functionality
function toggleClientFilter() {
    const dropdown = document.getElementById('clientFilterDropdown');
    const isVisible = dropdown.style.display !== 'none';
    
    // Close all other dropdowns first
    closeAllFilterDropdowns();
    
    if (!isVisible) {
        dropdown.style.display = 'block';
        // Position the dropdown
        positionFilterDropdown(dropdown);
    }
}

function closeClientFilter() {
    document.getElementById('clientFilterDropdown').style.display = 'none';
}

function closeAllFilterDropdowns() {
    const dropdowns = document.querySelectorAll('.filter-dropdown');
    dropdowns.forEach(dropdown => {
        dropdown.style.display = 'none';
    });
}

function positionFilterDropdown(dropdown) {
    const rect = dropdown.parentElement.getBoundingClientRect();
    dropdown.style.position = 'absolute';
    dropdown.style.top = '100%';
    dropdown.style.left = '0';
    dropdown.style.zIndex = '1000';
}

function selectAllClients() {
    const checkboxes = document.querySelectorAll('.client-checkbox');
    checkboxes.forEach(checkbox => {
        if (checkbox.closest('.client-option').style.display !== 'none') {
            checkbox.checked = true;
        }
    });
}

function clearAllClients() {
    const checkboxes = document.querySelectorAll('.client-checkbox');
    checkboxes.forEach(checkbox => {
        checkbox.checked = false;
    });
}

function filterClientList() {
    const searchTerm = document.getElementById('clientFilterSearch').value.toLowerCase();
    const options = document.querySelectorAll('.client-option');
    
    options.forEach(option => {
        const clientName = option.getAttribute('data-client-name');
        if (clientName.includes(searchTerm)) {
            option.style.display = 'block';
        } else {
            option.style.display = 'none';
        }
    });
}

function applyClientFilter() {
    const selectedClients = [];
    const checkboxes = document.querySelectorAll('.client-checkbox:checked');
    
    checkboxes.forEach(checkbox => {
        selectedClients.push(checkbox.value);
    });
    
    // Create form and submit
    const form = document.createElement('form');
    form.method = 'POST';
    form.action = '<?php echo Route::_('index.php?option=com_cotizaciones&view=cotizaciones'); ?>';
    
    // Add selected clients as hidden inputs
    selectedClients.forEach(clientId => {
        const input = document.createElement('input');
        input.type = 'hidden';
        input.name = 'filter_clients[]';
        input.value = clientId;
        form.appendChild(input);
    });
    
    // Add token
    const tokenInput = document.createElement('input');
    tokenInput.type = 'hidden';
    tokenInput.name = '<?php echo Session::getFormToken(); ?>';
    tokenInput.value = '1';
    form.appendChild(tokenInput);
    
    document.body.appendChild(form);
    form.submit();
}

// Close dropdowns when clicking outside
document.addEventListener('click', function(event) {
    if (!event.target.closest('.filterable-column')) {
        closeAllFilterDropdowns();
    }
});

// Prevent dropdown from closing when clicking inside
document.addEventListener('click', function(event) {
    if (event.target.closest('.filter-dropdown')) {
        event.stopPropagation();
    }
});
</script>