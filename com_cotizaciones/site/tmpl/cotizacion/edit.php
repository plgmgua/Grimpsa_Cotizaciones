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
                                        <small class="form-text text-muted">Se genera automáticamente</small>
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
                                        <small class="form-text text-muted">Ingrese el ID del cliente</small>
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

            <!-- Form Actions -->
            <div class="form-actions">
                <div class="btn-toolbar" role="toolbar">
                    <div class="btn-group me-2" role="group">
                        <button type="button" class="btn btn-success" onclick="saveQuote()">
                            <i class="fas fa-save"></i> Guardar
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
</script>