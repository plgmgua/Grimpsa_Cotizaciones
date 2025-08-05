<?php
/**
 * @package     Grimpsa.Administrator
 * @subpackage  com_cotizaciones
 *
 * @copyright   Copyright (C) 2025 Grimpsa. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

namespace Grimpsa\Component\Cotizaciones\Administrator\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\AdminModel;
use Joomla\CMS\Table\Table;

/**
 * Configuration model for the Cotizaciones component.
 */
class ConfigModel extends AdminModel
{
    /**
     * Method to get the record form.
     *
     * @param   array    $data      Data for the form.
     * @param   boolean  $loadData  True if the form is to load its own data (default case), false if not.
     *
     * @return  \JForm|boolean  A \JForm object on success, false on failure
     */
    public function getForm($data = [], $loadData = true)
    {
        // Get the form.
        $form = $this->loadForm(
            'com_cotizaciones.config', 
            'config', 
            [
                'control' => 'jform', 
                'load_data' => $loadData
            ]
        );

        if (empty($form)) {
            return false;
        }

        return $form;
    }

    /**
     * Method to get the configuration data.
     *
     * @return  object  The configuration data.
     */
    public function getItem($pk = null)
    {
        $params = ComponentHelper::getParams('com_cotizaciones');
        
        return (object) [
            'odoo_url' => $params->get('odoo_url', 'https://grupoimpre.odoo.com/xmlrpc/2/object'),
            'odoo_database' => $params->get('odoo_database', 'grupoimpre'),
            'odoo_username' => $params->get('odoo_username', 'admin'),
            'odoo_api_key' => $params->get('odoo_api_key', ''),
            'quotes_per_page' => $params->get('quotes_per_page', 20),
            'enable_debug' => $params->get('enable_debug', 0),
            'connection_timeout' => $params->get('connection_timeout', 30),
            'enable_ssl_verify' => $params->get('enable_ssl_verify', 0)
        ];
    }

    /**
     * Method to save the configuration.
     *
     * @param   array  $data  The configuration data.
     *
     * @return  boolean  True on success, false on failure.
     */
    public function save($data)
    {
        $db = Factory::getDbo();
        
        try {
            // Get current component parameters
            $query = $db->getQuery(true)
                ->select('params')
                ->from('#__extensions')
                ->where('element = ' . $db->quote('com_cotizaciones'))
                ->where('type = ' . $db->quote('component'));
            
            $db->setQuery($query);
            $currentParams = $db->loadResult();
            
            // Decode current parameters
            $params = json_decode($currentParams, true);
            if (!is_array($params)) {
                $params = [];
            }
            
            // Update with new values
            $params['odoo_url'] = $data['odoo_url'] ?? '';
            $params['odoo_database'] = $data['odoo_database'] ?? '';
            $params['odoo_username'] = $data['odoo_username'] ?? '';
            $params['odoo_api_key'] = $data['odoo_api_key'] ?? '';
            $params['quotes_per_page'] = (int) ($data['quotes_per_page'] ?? 20);
            $params['enable_debug'] = (int) ($data['enable_debug'] ?? 0);
            $params['connection_timeout'] = (int) ($data['connection_timeout'] ?? 30);
            $params['enable_ssl_verify'] = (int) ($data['enable_ssl_verify'] ?? 0);
            
            // Encode parameters
            $encodedParams = json_encode($params);
            
            // Update database
            $query = $db->getQuery(true)
                ->update('#__extensions')
                ->set('params = ' . $db->quote($encodedParams))
                ->where('element = ' . $db->quote('com_cotizaciones'))
                ->where('type = ' . $db->quote('component'));
            
            $db->setQuery($query);
            $result = $db->execute();
            
            // Clear component cache
            $cache = Factory::getCache('com_cotizaciones');
            $cache->clean();
            
            return $result;
            
        } catch (Exception $e) {
            Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Method to get the data that should be injected in the form.
     *
     * @return  mixed  The data for the form.
     */
    protected function loadFormData()
    {
        return $this->getItem();
    }

    /**
     * Method to get a table object, load it if necessary.
     *
     * @param   string  $name     The table name. Optional.
     * @param   string  $prefix   The class prefix. Optional.
     * @param   array   $options  Configuration array for model. Optional.
     *
     * @return  Table  A Table object
     */
    public function getTable($name = '', $prefix = '', $options = [])
    {
        return Table::getInstance('Extension', 'JTable');
    }
}