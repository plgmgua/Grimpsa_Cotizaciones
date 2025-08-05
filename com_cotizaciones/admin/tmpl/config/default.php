<?php
/**
 * @package     Grimpsa.Administrator
 * @subpackage  com_cotizaciones
 *
 * @copyright   Copyright (C) 2025 Grimpsa. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;

HTMLHelper::_('behavior.formvalidator');
HTMLHelper::_('behavior.keepalive');
?>

<form action="<?php echo Route::_('index.php?option=com_cotizaciones&view=config'); ?>" 
      method="post" name="adminForm" id="adminForm" class="form-validate">

    <div class="row">
        <div class="col-lg-9">
            <div class="card">
                <div class="card-header">
                    <h3 class="card-title">
                        <i class="fas fa-cog"></i>
                        <?php echo Text::_('COM_COTIZACIONES_CONFIG_TITLE'); ?>
                    </h3>
                </div>
                <div class="card-body">
                    <?php echo $this->form->renderFieldset('odoo_connection'); ?>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h4 class="card-title">
                        <i class="fas fa-display"></i>
                        <?php echo Text::_('COM_COTIZACIONES_CONFIG_DISPLAY_OPTIONS'); ?>
                    </h4>
                </div>
                <div class="card-body">
                    <?php echo $this->form->renderFieldset('display_options'); ?>
                </div>
            </div>

            <div class="card mt-3">
                <div class="card-header">
                    <h4 class="card-title">
                        <i class="fas fa-tools"></i>
                        <?php echo Text::_('COM_COTIZACIONES_CONFIG_ADVANCED_OPTIONS'); ?>
                    </h4>
                </div>
                <div class="card-body">
                    <?php echo $this->form->renderFieldset('advanced_options'); ?>
                </div>
            </div>
        </div>

        <div class="col-lg-3">
            <div class="card">
                <div class="card-header">
                    <h4 class="card-title">
                        <i class="fas fa-info-circle"></i>
                        <?php echo Text::_('COM_COTIZACIONES_CONFIG_HELP_TITLE'); ?>
                    </h4>
                </div>
                <div class="card-body">
                    <div class="alert alert-info">
                        <h6><i class="fas fa-lightbulb"></i> <?php echo Text::_('COM_COTIZACIONES_CONFIG_HELP_ODOO_SETUP'); ?></h6>
                        <ol class="small">
                            <li><?php echo Text::_('COM_COTIZACIONES_CONFIG_HELP_STEP_1'); ?></li>
                            <li><?php echo Text::_('COM_COTIZACIONES_CONFIG_HELP_STEP_2'); ?></li>
                            <li><?php echo Text::_('COM_COTIZACIONES_CONFIG_HELP_STEP_3'); ?></li>
                            <li><?php echo Text::_('COM_COTIZACIONES_CONFIG_HELP_STEP_4'); ?></li>
                        </ol>
                    </div>

                    <div class="alert alert-warning">
                        <h6><i class="fas fa-exclamation-triangle"></i> <?php echo Text::_('COM_COTIZACIONES_CONFIG_SECURITY_TITLE'); ?></h6>
                        <p class="small"><?php echo Text::_('COM_COTIZACIONES_CONFIG_SECURITY_DESC'); ?></p>
                    </div>

                    <div class="d-grid">
                        <button type="button" class="btn btn-primary" onclick="testConnection()">
                            <i class="fas fa-broadcast-tower"></i>
                            <?php echo Text::_('COM_COTIZACIONES_CONFIG_TEST_CONNECTION'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <input type="hidden" name="task" value="" />
    <input type="hidden" name="test_connection" id="test_connection" value="0" />
    <?php echo HTMLHelper::_('form.token'); ?>
</form>

<script>
function testConnection() {
    document.getElementById('test_connection').value = '1';
    document.querySelector('input[name="task"]').value = 'config.save';
    document.getElementById('adminForm').submit();
}
</script>