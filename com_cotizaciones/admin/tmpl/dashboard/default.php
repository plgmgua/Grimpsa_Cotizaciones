<?php
/**
 * @package     Grimpsa.Administrator
 * @subpackage  com_cotizaciones
 *
 * @copyright   Copyright (C) 2025 Grimpsa. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\Router\Route;
?>

<div class="row">
    <div class="col-md-12">
        <div class="card">
            <div class="card-header">
                <h3 class="card-title">
                    <?php echo Text::_('COM_COTIZACIONES_DASHBOARD_TITLE'); ?>
                </h3>
            </div>
            <div class="card-body">
                <p><?php echo Text::_('COM_COTIZACIONES_DASHBOARD_DESC'); ?></p>
                
                <div class="row">
                    <div class="col-md-6">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo Text::_('COM_COTIZACIONES_DASHBOARD_FRONTEND'); ?></h5>
                                <p class="card-text"><?php echo Text::_('COM_COTIZACIONES_DASHBOARD_FRONTEND_DESC'); ?></p>
                                <a href="<?php echo Route::_('index.php?option=com_cotizaciones', false); ?>" 
                                   class="btn btn-light" target="_blank">
                                    <?php echo Text::_('COM_COTIZACIONES_DASHBOARD_VIEW_FRONTEND'); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h5 class="card-title"><?php echo Text::_('COM_COTIZACIONES_DASHBOARD_CONFIGURATION'); ?></h5>
                                <p class="card-text"><?php echo Text::_('COM_COTIZACIONES_DASHBOARD_CONFIGURATION_DESC'); ?></p>
                                <a href="<?php echo Route::_('index.php?option=com_cotizaciones&view=config'); ?>" 
                                   class="btn btn-light">
                                    <?php echo Text::_('COM_COTIZACIONES_DASHBOARD_CONFIGURE'); ?>
                                </a>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="mt-4">
                    <h4><?php echo Text::_('COM_COTIZACIONES_DASHBOARD_FEATURES'); ?></h4>
                    <ul class="list-group">
                        <li class="list-group-item">
                            <i class="fas fa-check text-success me-2"></i>
                            <?php echo Text::_('COM_COTIZACIONES_DASHBOARD_FEATURE_1'); ?>
                        </li>
                        <li class="list-group-item">
                            <i class="fas fa-check text-success me-2"></i>
                            <?php echo Text::_('COM_COTIZACIONES_DASHBOARD_FEATURE_2'); ?>
                        </li>
                        <li class="list-group-item">
                            <i class="fas fa-check text-success me-2"></i>
                            <?php echo Text::_('COM_COTIZACIONES_DASHBOARD_FEATURE_3'); ?>
                        </li>
                        <li class="list-group-item">
                            <i class="fas fa-check text-success me-2"></i>
                            <?php echo Text::_('COM_COTIZACIONES_DASHBOARD_FEATURE_4'); ?>
                        </li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>