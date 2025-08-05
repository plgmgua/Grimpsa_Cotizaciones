<?php
/**
 * @package     Grimpsa.Site
 * @subpackage  com_cotizaciones
 *
 * @copyright   Copyright (C) 2025 Grimpsa. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

defined('_JEXEC') or die;

use Joomla\CMS\Router\Route;

// This is the default view for cotizacion - redirect to edit layout
$quoteId = isset($this->item->id) ? (int)$this->item->id : 0;
$editUrl = Route::_('index.php?option=com_cotizaciones&view=cotizacion&layout=edit&id=' . $quoteId);

// Redirect to edit layout
header('Location: ' . $editUrl);
exit;
?>