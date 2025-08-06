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
                $redirectUrl = Route::_('index.php?option=com_cotizaciones&view=cotizacion&layout=edit&id=' . $quoteId);
            } else {
                $result = $model->createQuote($data);
                $message = 'Cotización creada exitosamente';
                if ($result !== false) {
                    $quoteId = $result;
                    $redirectUrl = Route::_('index.php?option=com_cotizaciones&view=cotizacion&layout=edit&id=' . $quoteId);
                } else {
                    $redirectUrl = Route::_('index.php?option=com_cotizaciones&view=cotizacion&layout=edit&id=0');
                }
            }

            if ($result !== false) {
                $this->app->enqueueMessage($message, 'success');
                $this->setRedirect($redirectUrl);
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
     * Add a new quote line
     *
     * @return  boolean  True if successful, false otherwise.
     */
    public function addLine()
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

        $quoteId = $this->input->getInt('quote_id');
        $description = $this->input->getString('line_description');
        $quantity = $this->input->getFloat('line_quantity');
        $price = $this->input->getFloat('line_price');
        
        if (!$quoteId || !$description || !$quantity || !$price) {
            $this->app->enqueueMessage('Todos los campos son requeridos', 'error');
            $this->setRedirect(Route::_('index.php?option=com_cotizaciones&view=cotizacion&layout=edit&id=' . $quoteId));
            return false;
        }
        
        try {
            $helper = new \Grimpsa\Component\Cotizaciones\Site\Helper\OdooHelper();
            
            // Generate product name (incremental)
            $model = $this->getModel('Cotizacion');
            $quote = $model->getItem($quoteId);
            $existingLines = $model->getQuoteLines($quoteId);
            $lineNumber = count($existingLines) + 1;
            $productName = $quote->name . '-' . str_pad($lineNumber, 2, '0', STR_PAD_LEFT);
            
            $lineId = $helper->createQuoteLine($quoteId, $productName, $description, $quantity, $price);
            
            if ($lineId) {
                $this->app->enqueueMessage('Línea agregada exitosamente', 'success');
            } else {
                $this->app->enqueueMessage('Error al agregar la línea', 'error');
            }
        } catch (Exception $e) {
            $this->app->enqueueMessage('Error: ' . $e->getMessage(), 'error');
        }
        
        $this->setRedirect(Route::_('index.php?option=com_cotizaciones&view=cotizacion&layout=edit&id=' . $quoteId));
        return true;
    }

    /**
     * Update a quote line
     *
     * @return  boolean  True if successful, false otherwise.
     */
    public function updateLine()
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

        $lineId = $this->input->getInt('line_id');
        $description = $this->input->getString('edit_description');
        $quantity = $this->input->getFloat('edit_quantity');
        $price = $this->input->getFloat('edit_price');
        $quoteId = $this->input->getInt('id');
        
        if (!$lineId || !$description || !$quantity || !$price) {
            $this->app->enqueueMessage('Todos los campos son requeridos', 'error');
            $this->setRedirect(Route::_('index.php?option=com_cotizaciones&view=cotizacion&layout=edit&id=' . $quoteId));
            return false;
        }
        
        try {
            $helper = new \Grimpsa\Component\Cotizaciones\Site\Helper\OdooHelper();
            $result = $helper->updateQuoteLine($lineId, $description, $quantity, $price);
            
            if ($result) {
                $this->app->enqueueMessage('Línea actualizada exitosamente', 'success');
            } else {
                $this->app->enqueueMessage('Error al actualizar la línea', 'error');
            }
        } catch (Exception $e) {
            $this->app->enqueueMessage('Error: ' . $e->getMessage(), 'error');
        }
        
        $this->setRedirect(Route::_('index.php?option=com_cotizaciones&view=cotizacion&layout=edit&id=' . $quoteId));
        return true;
    }

    /**
     * Delete a quote line
     *
     * @return  boolean  True if successful, false otherwise.
     */
    public function deleteLine()
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

        $lineId = $this->input->getInt('line_id');
        $quoteId = $this->input->getInt('id');
        
        if (!$lineId) {
            $this->app->enqueueMessage('ID de línea requerido', 'error');
            $this->setRedirect(Route::_('index.php?option=com_cotizaciones&view=cotizacion&layout=edit&id=' . $quoteId));
            return false;
        }
        
        try {
            $helper = new \Grimpsa\Component\Cotizaciones\Site\Helper\OdooHelper();
            $result = $helper->deleteQuoteLine($lineId);
            
            if ($result) {
                $this->app->enqueueMessage('Línea eliminada exitosamente', 'success');
            } else {
                $this->app->enqueueMessage('Error al eliminar la línea', 'error');
            }
        } catch (Exception $e) {
            $this->app->enqueueMessage('Error: ' . $e->getMessage(), 'error');
        }
        
        $this->setRedirect(Route::_('index.php?option=com_cotizaciones&view=cotizacion&layout=edit&id=' . $quoteId));
        return true;
    }

    /**
     * Search clients via AJAX
     *
     * @return  void
     */
    public function searchClients()
    {
        // Check for request forgeries
        if (!Session::checkToken()) {
            echo json_encode(['error' => 'Invalid token']);
            exit;
        }

        $user = Factory::getUser();
        
        if ($user->guest) {
            echo json_encode(['error' => 'Not logged in']);
            exit;
        }

        $search = $this->input->getString('search', '');
        
        if (strlen($search) < 2) {
            echo json_encode(['clients' => []]);
            exit;
        }

        try {
            $helper = new \Grimpsa\Component\Cotizaciones\Site\Helper\OdooHelper();
            $clients = $helper->getClients($search, $user->name);
            
            echo json_encode(['clients' => $clients]);
        } catch (Exception $e) {
            echo json_encode(['error' => $e->getMessage()]);
        }
        
        exit;
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