<?php
/**
 * @package     Grimpsa.Site
 * @subpackage  com_cotizaciones
 *
 * @copyright   Copyright (C) 2025 Grimpsa. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

namespace Grimpsa\Component\Cotizaciones\Site\Controller;

defined('_JEXEC') or die;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Exception;

/**
 * Cotizaciones Component Controller
 */
class DisplayController extends BaseController
{
    /**
     * The default view.
     *
     * @var    string
     */
    protected $default_view = 'cotizaciones';

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
        try {
            $user = Factory::getUser();
            
            // Check if user is logged in
            if ($user->guest) {
                $this->app->enqueueMessage(Text::_('COM_COTIZACIONES_ERROR_LOGIN_REQUIRED'), 'warning');
                $this->app->redirect(Route::_('index.php?option=com_users&view=login'));
                return $this;
            }

            // Get the document object
            $document = $this->app->getDocument();
            
            // Set the default view if not set
            $vName = $this->input->get('view', $this->default_view);
            $layout = $this->input->get('layout', 'default');
            $this->input->set('view', $vName);
            
            // Handle cotizacion edit layout
            if ($vName === 'cotizacion' && $layout === 'edit') {
                $this->input->set('layout', 'edit');
                
                // For new quotes, ensure ID is 0
                $quoteId = $this->input->getInt('id');
                if ($quoteId === null || $quoteId < 0) {
                    $quoteId = 0;
                }
                $this->input->set('id', $quoteId);
            }

            parent::display($cachable, $urlparams);

            return $this;
            
        } catch (Exception $e) {
            // Log the error
            Factory::getApplication()->enqueueMessage('Error displaying view: ' . $e->getMessage(), 'error');
            
            // Redirect to a safe page
            $this->app->redirect(Route::_('index.php?option=com_cotizaciones&view=cotizaciones'));
            return $this;
        }
    }
}