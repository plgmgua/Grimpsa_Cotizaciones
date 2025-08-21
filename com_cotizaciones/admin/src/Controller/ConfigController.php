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

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\Controller\FormController;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Session\Session;
use Exception;

/**
 * Configuration controller class.
 */
class ConfigController extends FormController
{
    /**
     * Method to save the configuration.
     *
     * @return  boolean  True if successful, false otherwise.
     */
    public function save()
    {
        // Check for request forgeries
        if (!Session::checkToken()) {
            $this->app->enqueueMessage(Text::_('JINVALID_TOKEN'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_cotizaciones&view=config'));
            return false;
        }

        $model = $this->getModel('Config');
        $data = $this->input->post->get('jform', [], 'array');
        
        try {
            $result = $model->save($data);

            if ($result) {
                $this->app->enqueueMessage(Text::_('COM_COTIZACIONES_CONFIG_SAVED_SUCCESS'), 'success');
                
                // Test connection if requested
                if ($this->input->post->get('test_connection', false)) {
                    $helper = new \Grimpsa\Component\Cotizaciones\Site\Helper\OdooHelper();
                    $status = $helper->getConnectionStatus();
                    
                    if ($status['authentication_test']) {
                        $this->app->enqueueMessage(Text::_('COM_COTIZACIONES_CONFIG_CONNECTION_SUCCESS'), 'success');
                    } else {
                        $this->app->enqueueMessage(Text::_('COM_COTIZACIONES_CONFIG_CONNECTION_FAILED') . ': ' . $status['error_message'], 'warning');
                    }
                }
            } else {
                $this->app->enqueueMessage(Text::_('COM_COTIZACIONES_CONFIG_SAVE_FAILED'), 'error');
            }
        } catch (Exception $e) {
            $this->app->enqueueMessage($e->getMessage(), 'error');
        }

        $this->setRedirect(Route::_('index.php?option=com_cotizaciones&view=config'));
        return true;
    }

    /**
     * Method to test the Odoo connection.
     *
     * @return  boolean  True if successful, false otherwise.
     */
    public function testConnection()
    {
        // Check for request forgeries
        if (!Session::checkToken()) {
            $this->app->enqueueMessage(Text::_('JINVALID_TOKEN'), 'error');
            $this->setRedirect(Route::_('index.php?option=com_cotizaciones&view=config'));
            return false;
        }

        try {
            $helper = new \Grimpsa\Component\Cotizaciones\Site\Helper\OdooHelper();
            $status = $helper->getConnectionStatus();
            
            if ($status['authentication_test']) {
                $this->app->enqueueMessage(Text::_('COM_COTIZACIONES_CONFIG_CONNECTION_SUCCESS') . ' (UID: ' . $status['uid'] . ')', 'success');
            } else {
                $message = Text::_('COM_COTIZACIONES_CONFIG_CONNECTION_FAILED');
                if (!empty($status['error_message'])) {
                    $message .= ': ' . $status['error_message'];
                }
                $this->app->enqueueMessage($message, 'error');
            }
        } catch (Exception $e) {
            $this->app->enqueueMessage('Connection test failed: ' . $e->getMessage(), 'error');
        }

        $this->setRedirect(Route::_('index.php?option=com_cotizaciones&view=config'));
        return true;
    }

    /**
     * Method to cancel the configuration.
     *
     * @return  boolean  True if successful, false otherwise.
     */
    public function cancel()
    {
        $this->setRedirect(Route::_('index.php?option=com_cotizaciones'));
        return true;
    }
}