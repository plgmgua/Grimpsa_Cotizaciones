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
     * Search clients via AJAX
     *
     * @return  void
     */
    public function searchClients()
    {
        $app = Factory::getApplication();
        $input = $app->input;
        
        // Set JSON response
        $app->getDocument()->setMimeEncoding('application/json');
        
        $search = $input->getString('search', '');
        
        if (empty($search) || strlen($search) < 2) {
            echo json_encode(['clients' => []]);
            $app->close();
        }
        
        try {
            $helper = new \Grimpsa\Component\Cotizaciones\Site\Helper\OdooHelper();
            $clients = $helper->getClients($search);
            
            echo json_encode(['clients' => $clients]);
        } catch (Exception $e) {
            echo json_encode(['clients' => [], 'error' => $e->getMessage()]);
        }
        
        $app->close();
    }

    /**
     * Create quote line via AJAX
     *
     * @return  void
     */
    public function createLine()
    {
        $app = Factory::getApplication();
        $input = $app->input;
        
        // Set JSON response
        $app->getDocument()->setMimeEncoding('application/json');
        
        $quoteId = $input->getInt('quote_id');
        $productName = $input->getString('product_name');
        $description = $input->getString('description');
        $quantity = $input->getFloat('quantity');
        $price = $input->getFloat('price');
        
        if (!$quoteId || !$productName || !$description || !$quantity || !$price) {
            echo json_encode(['success' => false, 'message' => 'Missing required parameters']);
            $app->close();
        }
        
        try {
            $helper = new \Grimpsa\Component\Cotizaciones\Site\Helper\OdooHelper();
            $lineId = $helper->createQuoteLine($quoteId, $productName, $description, $quantity, $price);
            
            if ($lineId) {
                echo json_encode(['success' => true, 'line_id' => $lineId]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to create quote line']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        
        $app->close();
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