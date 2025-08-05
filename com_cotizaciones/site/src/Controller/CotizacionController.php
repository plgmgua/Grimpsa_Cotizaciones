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

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;

/**
 * Cotizacion controller class.
 */
class CotizacionController extends FormController
{
    /**
     * Method to check out an item for editing and redirect to the edit form.
     *
     * @return  boolean  True if access level check and checkout passes, false otherwise.
     */
    public function edit($key = null, $urlVar = null)
    {
        $user = Factory::getUser();
        
        if ($user->guest) {
            $this->app->enqueueMessage(Text::_('COM_COTIZACIONES_ERROR_LOGIN_REQUIRED'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_users&view=login'));
            return false;
        }

        $quoteId = $this->input->getInt('id', 0);
        
        // Redirect to edit layout
        $this->setRedirect(Route::_('index.php?option=com_cotizaciones&view=cotizacion&layout=edit&id=' . $quoteId));
        return true;
    }

    /**
     * Method to add a new record.
     *
     * @return  boolean  True if the record can be added, false if not.
     */
    public function add()
    {
        $user = Factory::getUser();
        
        if ($user->guest) {
            $this->app->enqueueMessage(Text::_('COM_COTIZACIONES_ERROR_LOGIN_REQUIRED'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_users&view=login'));
            return false;
        }

        // Redirect to edit layout for new quote
        $this->setRedirect(Route::_('index.php?option=com_cotizaciones&view=cotizacion&layout=edit&id=0'));
        return true;
    }

    /**
     * Method to save a quote.
     *
     * @param   string  $key     The name of the primary key of the URL variable.
     * @param   string  $urlVar  The name of the URL variable if different from the primary key
     *
     * @return  boolean  True if successful, false otherwise.
     */
    public function save($key = null, $urlVar = null)
    {
        // Check for request forgeries
        if (!Session::checkToken()) {
            $this->app->enqueueMessage(Text::_('JINVALID_TOKEN'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_cotizaciones&view=cotizaciones'));
            return false;
        }

        $user = Factory::getUser();
        
        if ($user->guest) {
            $this->app->enqueueMessage(Text::_('COM_COTIZACIONES_ERROR_LOGIN_REQUIRED'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_users&view=login'));
            return false;
        }

        $model = $this->getModel('Cotizacion');
        $data = $this->input->post->get('jform', [], 'array');
        
        // Ensure the sales agent field is always set
        $data['x_studio_agente_de_ventas_1'] = $user->name;

        $quoteId = $this->input->getInt('id', 0);
        
        try {
            if ($quoteId > 0) {
                $result = $model->updateQuote($quoteId, $data);
                $message = 'Cotización actualizada exitosamente';
            } else {
                $result = $model->createQuote($data);
                $message = 'Cotización creada exitosamente';
                $quoteId = $result;
            }

            if ($result !== false) {
                $this->app->enqueueMessage($message, 'success');
                $this->setRedirect(Route::_('index.php?option=com_cotizaciones&view=cotizaciones'));
            } else {
                $this->app->enqueueMessage('Error al guardar la cotización', 'error');
                $this->setRedirect(Route::_('index.php?option=com_cotizaciones&view=cotizacion&layout=edit&id=' . $quoteId));
            }
        } catch (Exception $e) {
            $this->app->enqueueMessage($e->getMessage(), 'error');
            $this->setRedirect(Route::_('index.php?option=com_cotizaciones&view=cotizacion&layout=edit&id=' . $quoteId));
        }

        return true;
    }

    /**
     * Method to cancel an operation
     *
     * @param   string  $key  The name of the primary key of the URL variable.
     *
     * @return  boolean  True if access level checks pass, false otherwise.
     */
    public function cancel($key = null)
    {
        $this->setRedirect(Route::_('index.php?option=com_cotizaciones&view=cotizaciones'));
        return true;
    }
}