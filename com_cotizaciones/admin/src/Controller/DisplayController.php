<?php
/**
 * @package     Grimpsa.Administrator
 * @subpackage  com_cotizaciones
 *
 * @copyright   Copyright (C) 2025 Grimpsa. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

namespace Grimpsa\Component\Cotizaciones\Administrator\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\MVC\Controller\BaseController;

/**
 * Cotizaciones master display controller.
 */
class DisplayController extends BaseController
{
    /**
     * The default view.
     *
     * @var    string
     */
    protected $default_view = 'dashboard';

    /**
     * Method to display a view.
     *
     * @param   boolean  $cachable   If true, the view output will be cached
     * @param   array    $urlparams  An array of safe URL parameters and their variable types
     *
     * @return  static  This object to support chaining.
     */
    public function display($cachable = false, $urlparams = [])
    {
        $view = $this->input->get('view', $this->default_view);
        
        // Allow config view
        if ($view === 'config') {
            $this->input->set('view', 'config');
        }
        
        return parent::display($cachable, $urlparams);
    }
}