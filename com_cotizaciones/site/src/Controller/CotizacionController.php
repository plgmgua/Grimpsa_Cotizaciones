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
        
        // Handle quote lines for new quotes
        $quoteLinesJson = $this->input->post->getString('quote_lines_data', '');
        $quoteLines = [];
        if (!empty($quoteLinesJson)) {
            $quoteLines = json_decode($quoteLinesJson, true);
            if (!is_array($quoteLines)) {
                $quoteLines = [];
            }
        }
        
        // Debug logging if enabled
        if ($this->app->get('debug', 0)) {
            $this->app->enqueueMessage('Setting sales agent: ' . $user->name, 'info');
            if (!empty($quoteLines)) {
                $this->app->enqueueMessage('Quote lines count: ' . count($quoteLines), 'info');
            }
        }

        $quoteId = $this->input->getInt('id', 0);
        
        try {
            if ($quoteId > 0) {
                $result = $model->updateQuote($quoteId, $data);
                
                // Handle quote lines for existing quotes
                if (!empty($quoteLines)) {
                    $helper = new \Grimpsa\Component\Cotizaciones\Site\Helper\OdooHelper();
                    foreach ($quoteLines as $line) {
                        if (isset($line['is_new']) && $line['is_new']) {
                            // Create product first
                            $productId = $helper->createProduct(
                                $line['description'],
                                'PROD-' . $line['product_code'],
                                (float)$line['price']
                            );
                            
                            // Create quote line
                            $helper->createQuoteLine(
                                $quoteId,
                                $productId,
                                $line['description'],
                                (float)$line['quantity'],
                                (float)$line['price']
                            );
                        }
                    }
                }
                
                $message = 'Cotización actualizada exitosamente';
            } else {
                $result = $model->createQuote($data);
                
                if ($result !== false) {
                    $quoteId = $result;
                    
                    // Handle quote lines for new quotes
                    if (!empty($quoteLines)) {
                        $helper = new \Grimpsa\Component\Cotizaciones\Site\Helper\OdooHelper();
                        foreach ($quoteLines as $line) {
                            // Create product first
                            $productId = $helper->createProduct(
                                $line['description'],
                                'PROD-' . $line['product_code'],
                                (float)$line['price']
                            );
                            
                            // Create quote line
                            $helper->createQuoteLine(
                                $quoteId,
                                $productId,
                                $line['description'],
                                (float)$line['quantity'],
                                (float)$line['price']
                            );
                        }
                    }
                }
                
                $message = 'Cotización creada exitosamente';
            }

            if ($result !== false) {
                $this->app->enqueueMessage($message, 'success');
                // Redirect to edit the created/updated quote
                $this->setRedirect(Route::_('index.php?option=com_cotizaciones&view=cotizacion&layout=edit&id=' . $quoteId));
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
     * Method to apply changes to a quote and stay on the edit form.
     *
     * @return  boolean  True if successful, false otherwise.
     */
    public function apply()
    {
        // Check for request forgeries
        if (!Session::checkToken()) {
            $this->app->enqueueMessage(Text::_('JINVALID_TOKEN'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_cotizaciones&view=cotizaciones'));
            return false;
        }

        $user = Factory::getUser();
        
        if ($user->guest) {
            $this->app->enqueueMessage('Debes iniciar sesión para gestionar cotizaciones', 'error');
            $this->setRedirect(Route::_('index.php?option=com_users&view=login'));
            return false;
        }

        $model = $this->getModel('Cotizacion');
        $data = $this->input->post->get('jform', [], 'array');
        
        // Ensure the sales agent field is always set
        $data['x_studio_agente_de_ventas_1'] = $user->name;
        
        // Debug logging if enabled
        if ($this->app->get('debug', 0)) {
            $this->app->enqueueMessage('Setting sales agent: ' . $user->name, 'info');
        }

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
                $this->setRedirect(Route::_('index.php?option=com_cotizaciones&view=cotizacion&layout=edit&id=' . $quoteId));
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
     * Method to delete a quote.
     *
     * @return  boolean  True if successful, false otherwise.
     */
    public function delete()
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

        $quoteId = $this->input->getInt('id', 0);
        
        if ($quoteId <= 0) {
            $this->app->enqueueMessage(Text::_('COM_COTIZACIONES_ERROR_INVALID_QUOTE'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_cotizaciones&view=cotizaciones'));
            return false;
        }

        $model = $this->getModel('Cotizacion');
        
        try {
            $result = $model->deleteQuote($quoteId);
            
            if ($result) {
                $this->app->enqueueMessage(Text::_('COM_COTIZACIONES_QUOTE_DELETED_SUCCESS'), 'success');
            } else {
                $this->app->enqueueMessage(Text::_('COM_COTIZACIONES_ERROR_DELETE_FAILED'), 'error');
            }
        } catch (Exception $e) {
            $this->app->enqueueMessage('Error: ' . $e->getMessage(), 'error');
        }

        $this->setRedirect(Route::_('index.php?option=com_cotizaciones&view=cotizaciones'));
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

    /**
     * Search clients via AJAX
     *
     * @return  void
     */
    public function searchClients()
    {
        // Check for request forgeries
        if (!Session::checkToken()) {
            echo json_encode(['success' => false, 'message' => 'Invalid token']);
            return;
        }

        $user = Factory::getUser();
        
        if ($user->guest) {
            echo json_encode(['success' => false, 'message' => 'Login required']);
            return;
        }

        $search = $this->input->getString('search', '');
        
        if (strlen($search) < 2) {
            echo json_encode(['success' => false, 'message' => 'Search term too short']);
            return;
        }

        try {
            // Use the contacts helper to search clients
            if (class_exists('\Grimpsa\Component\OdooContacts\Site\Helper\OdooHelper')) {
                $contactsHelper = new \Grimpsa\Component\OdooContacts\Site\Helper\OdooHelper();
                $contacts = $contactsHelper->getContactsByAgent($user->name, 1, 20, $search);
                
                echo json_encode([
                    'success' => true,
                    'clients' => $contacts
                ]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Contacts helper not available']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }

        $this->app->close();
    }

    /**
     * Get quote lines via AJAX
     *
     * @return  void
     */
    public function getLines()
    {
        // Check for request forgeries
        if (!Session::checkToken()) {
            echo json_encode(['success' => false, 'message' => 'Invalid token']);
            return;
        }

        $user = Factory::getUser();
        
        if ($user->guest) {
            echo json_encode(['success' => false, 'message' => 'Login required']);
            return;
        }

        $quoteId = $this->input->getInt('quote_id', 0);
        
        if ($quoteId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid quote ID']);
            return;
        }

        try {
            $helper = new \Grimpsa\Component\Cotizaciones\Site\Helper\OdooHelper();
            $lines = $helper->getQuoteLines($quoteId);
            
            echo json_encode([
                'success' => true,
                'lines' => $lines
            ]);
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }

        $this->app->close();
    }

    /**
     * Add quote line via AJAX
     *
     * @return  void
     */
    public function addLine()
    {
        // Check for request forgeries
        if (!Session::checkToken()) {
            echo json_encode(['success' => false, 'message' => 'Invalid token']);
            return;
        }

        $user = Factory::getUser();
        
        if ($user->guest) {
            echo json_encode(['success' => false, 'message' => 'Login required']);
            return;
        }

        $quoteId = $this->input->getInt('quote_id', 0);
        $description = $this->input->getString('description', '');
        $quantity = $this->input->getFloat('quantity', 1.0);
        $price = $this->input->getFloat('price', 0.0);
        
        if ($quoteId <= 0 || empty($description)) {
            echo json_encode(['success' => false, 'message' => 'Missing required data']);
            return;
        }

        try {
            $helper = new \Grimpsa\Component\Cotizaciones\Site\Helper\OdooHelper();
            
            // Create product first
            $productId = $helper->createProduct($description);
            
            if ($productId) {
                // Create quote line
                $lineId = $helper->createQuoteLine($quoteId, $productId, $description, $quantity, $price);
                
                if ($lineId) {
                    echo json_encode(['success' => true, 'line_id' => $lineId]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to create quote line']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to create product']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }

        $this->app->close();
    }

    /**
     * Delete quote line via AJAX
     *
     * @return  void
     */
    public function deleteLine()
    {
        // Check for request forgeries
        if (!Session::checkToken()) {
            echo json_encode(['success' => false, 'message' => 'Invalid token']);
            return;
        }

        $user = Factory::getUser();
        
        if ($user->guest) {
            echo json_encode(['success' => false, 'message' => 'Login required']);
            return;
        }

        $lineId = $this->input->getInt('line_id', 0);
        
        if ($lineId <= 0) {
            echo json_encode(['success' => false, 'message' => 'Invalid line ID']);
            return;
        }

        try {
            $helper = new \Grimpsa\Component\Cotizaciones\Site\Helper\OdooHelper();
            $result = $helper->deleteQuoteLine($lineId);
            
            if ($result) {
                echo json_encode(['success' => true]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Failed to delete line']);
            }
        } catch (Exception $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }

        $this->app->close();
    }
}