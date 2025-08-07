<?php
/**
 * @package     Grimpsa.Site
 * @subpackage  com_cotizaciones
 *
 * @copyright   Copyright (C) 2025 Grimpsa. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

namespace Grimpsa\Component\Cotizaciones\Site\Helper;

defined('_JEXEC') or die;

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;

/**
 * Helper class for Odoo integration
 */
class OdooHelper
{
    /**
     * Get component configuration
     *
     * @return  array  Configuration array
     */
    private function getConfig()
    {
        $params = ComponentHelper::getParams('com_cotizaciones');
        
        return [
            'base_url' => $params->get('odoo_url', 'https://grupoimpre.odoo.com/xmlrpc/2'),
            'database' => $params->get('odoo_database', 'grupoimpre'),
            'username' => $params->get('odoo_username', 'admin'),
            'password' => $params->get('odoo_api_key', ''),
            'timeout' => $params->get('connection_timeout', 30),
            'debug' => $params->get('enable_debug', 0)
        ];
    }

    /**
     * Make XML-RPC call to Odoo
     *
     * @param   string  $endpoint  The endpoint (common or object)
     * @param   string  $method    The method to call
     * @param   array   $params    The parameters
     *
     * @return  mixed   The response
     * @throws  Exception
     */
    private function callOdoo($endpoint, $method, $params = [])
    {
        $config = $this->getConfig();
        
        // Build URL based on endpoint
        if ($endpoint === 'common') {
            $url = str_replace('/object', '/common', $config['base_url']);
        } else {
            $url = $config['base_url'];
        }
        
        // Prepare XML-RPC request
        $request = xmlrpc_encode_request($method, $params);
        
        // Create context
        $context = stream_context_create([
            'http' => [
                'method' => 'POST',
                'header' => 'Content-Type: text/xml',
                'content' => $request,
                'timeout' => $config['timeout']
            ]
        ]);
        
        // Make request
        $response = file_get_contents($url, false, $context);
        
        if ($response === false) {
            throw new \Exception('Failed to connect to Odoo server');
        }
        
        // Decode response
        $result = xmlrpc_decode($response);
        
        if (is_array($result) && xmlrpc_is_fault($result)) {
            throw new \Exception('Odoo error: ' . $result['faultString']);
        }
        
        return $result;
    }

    /**
     * Authenticate with Odoo
     *
     * @return  integer  User ID
     * @throws  Exception
     */
    private function authenticate()
    {
        $config = $this->getConfig();
        
        $uid = $this->callOdoo('common', 'login', [
            $config['database'],
            $config['username'],
            $config['password']
        ]);
        
        if (!$uid) {
            throw new \Exception('Authentication failed');
        }
        
        return $uid;
    }

    /**
     * Get quotes by sales agent
     *
     * @param   string   $agentName  Sales agent name
     * @param   integer  $page       Page number
     * @param   integer  $limit      Items per page
     * @param   string   $search     Search term
     * @param   string   $state      State filter
     *
     * @return  array    Array of quotes
     */
    public function getQuotesByAgent($agentName, $page = 1, $limit = 20, $search = '', $state = '')
    {
        try {
            $config = $this->getConfig();
            $uid = $this->authenticate();
            
            // Build domain
            $domain = [
                ['x_studio_agente_de_ventas_1', '=', $agentName]
            ];
            
            // Add search filter
            if (!empty($search)) {
                $domain[] = ['partner_id.name', 'ilike', '%' . trim($search) . '%'];
            }
            
            // Add state filter
            if (!empty($state)) {
                $domain[] = ['state', '=', $state];
            }
            
            // Calculate offset
            $offset = ($page - 1) * $limit;
            
            // Search quotes
            $quoteIds = $this->callOdoo('object', 'execute_kw', [
                $config['database'],
                $uid,
                $config['password'],
                'sale.order',
                'search',
                [$domain],
                ['offset' => $offset, 'limit' => $limit, 'order' => 'date_order desc']
            ]);
            
            if (empty($quoteIds)) {
                return [];
            }
            
            // Read quote data
            $quotes = $this->callOdoo('object', 'execute_kw', [
                $config['database'],
                $uid,
                $config['password'],
                'sale.order',
                'read',
                [$quoteIds],
                ['fields' => ['id', 'name', 'partner_id', 'date_order', 'amount_total', 'state']]
            ]);
            
            // Process quotes to add contact names
            foreach ($quotes as &$quote) {
                if (isset($quote['partner_id']) && is_array($quote['partner_id'])) {
                    $quote['contact_name'] = $quote['partner_id'][1];
                    $quote['partner_id'] = $quote['partner_id'][0];
                } else {
                    $quote['contact_name'] = 'Cliente no disponible';
                }
            }
            
            return $quotes;
            
        } catch (\Exception $e) {
            Factory::getApplication()->enqueueMessage('Error getting quotes: ' . $e->getMessage(), 'error');
            return [];
        }
    }

    /**
     * Get a single quote
     *
     * @param   integer  $quoteId  Quote ID
     *
     * @return  array|false  Quote data or false
     */
    public function getQuote($quoteId)
    {
        try {
            $config = $this->getConfig();
            $uid = $this->authenticate();
            
            $quotes = $this->callOdoo('object', 'execute_kw', [
                $config['database'],
                $uid,
                $config['password'],
                'sale.order',
                'read',
                [[$quoteId]],
                ['fields' => ['id', 'name', 'partner_id', 'date_order', 'amount_total', 'state', 'note']]
            ]);
            
            if (empty($quotes)) {
                return false;
            }
            
            $quote = $quotes[0];
            
            // Process partner info
            if (isset($quote['partner_id']) && is_array($quote['partner_id'])) {
                $quote['contact_name'] = $quote['partner_id'][1];
                $quote['partner_id'] = $quote['partner_id'][0];
            } else {
                $quote['contact_name'] = 'Cliente no disponible';
            }
            
            return $quote;
            
        } catch (\Exception $e) {
            Factory::getApplication()->enqueueMessage('Error getting quote: ' . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Get clients for search
     *
     * @param   string  $searchTerm  Search term
     * @param   string  $agentName   Sales agent name
     *
     * @return  array   Array of clients
     */
    public function getClients($searchTerm, $agentName)
    {
        try {
            $config = $this->getConfig();
            $uid = $this->authenticate();
            
            // Search for clients with partial name match (case insensitive)
            $domain = [
                ['name', 'ilike', '%' . trim($searchTerm) . '%'],
                ['is_company', '=', true]
            ];
            
            $clientIds = $this->callOdoo('object', 'execute_kw', [
                $config['database'],
                $uid,
                $config['password'],
                'res.partner',
                'search',
                [$domain],
                ['limit' => 50, 'order' => 'name']
            ]);
            
            if (empty($clientIds)) {
                return [];
            }
            
            $clients = $this->callOdoo('object', 'execute_kw', [
                $config['database'],
                $uid,
                $config['password'],
                'res.partner',
                'read',
                [$clientIds],
                ['fields' => ['id', 'name', 'email', 'phone']]
            ]);
            
            return $clients;
            
        } catch (\Exception $e) {
            Factory::getApplication()->enqueueMessage('Error searching clients: ' . $e->getMessage(), 'error');
            return [];
        }
    }

    /**
     * Get client by ID
     *
     * @param   integer  $clientId  Client ID
     *
     * @return  array|false  Client data or false
     */
    public function getClientById($clientId)
    {
        try {
            $config = $this->getConfig();
            $uid = $this->authenticate();
            
            $clients = $this->callOdoo('object', 'execute_kw', [
                $config['database'],
                $uid,
                $config['password'],
                'res.partner',
                'read',
                [[$clientId]],
                ['fields' => ['id', 'name', 'email', 'phone']]
            ]);
            
            return !empty($clients) ? $clients[0] : false;
            
        } catch (\Exception $e) {
            Factory::getApplication()->enqueueMessage('Error getting client: ' . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Create a new quote
     *
     * @param   array  $data  Quote data
     *
     * @return  integer|false  Quote ID or false
     */
    public function createQuote($data)
    {
        try {
            $config = $this->getConfig();
            $uid = $this->authenticate();
            
            $quoteData = [
                'partner_id' => (int) $data['partner_id'],
                'date_order' => $data['date_order'],
                'x_studio_agente_de_ventas_1' => $data['x_studio_agente_de_ventas_1'],
                'note' => isset($data['note']) ? $data['note'] : ''
            ];
            
            $quoteId = $this->callOdoo('object', 'execute_kw', [
                $config['database'],
                $uid,
                $config['password'],
                'sale.order',
                'create',
                [$quoteData]
            ]);
            
            return $quoteId;
            
        } catch (\Exception $e) {
            Factory::getApplication()->enqueueMessage('Error creating quote: ' . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Update a quote
     *
     * @param   integer  $quoteId  Quote ID
     * @param   array    $data     Quote data
     *
     * @return  boolean  Success
     */
    public function updateQuote($quoteId, $data)
    {
        try {
            $config = $this->getConfig();
            $uid = $this->authenticate();
            
            $updateData = [];
            
            if (isset($data['date_order'])) {
                $updateData['date_order'] = $data['date_order'];
            }
            
            if (isset($data['note'])) {
                $updateData['note'] = $data['note'];
            }
            
            if (empty($updateData)) {
                return true;
            }
            
            $result = $this->callOdoo('object', 'execute_kw', [
                $config['database'],
                $uid,
                $config['password'],
                'sale.order',
                'write',
                [[$quoteId], $updateData]
            ]);
            
            return $result;
            
        } catch (\Exception $e) {
            Factory::getApplication()->enqueueMessage('Error updating quote: ' . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Get quote lines
     *
     * @param   integer  $quoteId  Quote ID
     *
     * @return  array    Array of quote lines
     */
    public function getQuoteLines($quoteId)
    {
        try {
            $config = $this->getConfig();
            $uid = $this->authenticate();
            
            // Search for quote lines
            $lineIds = $this->callOdoo('object', 'execute_kw', [
                $config['database'],
                $uid,
                $config['password'],
                'sale.order.line',
                'search',
                [['order_id', '=', $quoteId]]
            ]);
            
            if (empty($lineIds)) {
                return [];
            }
            
            // Read line data
            $lines = $this->callOdoo('object', 'execute_kw', [
                $config['database'],
                $uid,
                $config['password'],
                'sale.order.line',
                'read',
                [$lineIds],
                ['fields' => ['id', 'name', 'product_uom_qty', 'price_unit', 'product_id']]
            ]);
            
            // Process lines to add product names
            foreach ($lines as &$line) {
                if (isset($line['product_id']) && is_array($line['product_id'])) {
                    $line['product_name'] = $line['product_id'][1];
                } else {
                    $line['product_name'] = 'Producto';
                }
            }
            
            return $lines;
            
        } catch (\Exception $e) {
            Factory::getApplication()->enqueueMessage('Error getting quote lines: ' . $e->getMessage(), 'error');
            return [];
        }
    }

    /**
     * Create a quote line
     *
     * @param   integer  $quoteId     Quote ID
     * @param   string   $productName Product name
     * @param   string   $description Description
     * @param   float    $quantity    Quantity
     * @param   float    $price       Price
     *
     * @return  integer|false  Line ID or false
     */
    public function createQuoteLine($quoteId, $productName, $description, $quantity, $price)
    {
        try {
            $config = $this->getConfig();
            $uid = $this->authenticate();
            
            // First create a product
            $productId = $this->callOdoo('object', 'execute_kw', [
                $config['database'],
                $uid,
                $config['password'],
                'product.product',
                'create',
                [[
                    'name' => $productName,
                    'type' => 'service',
                    'list_price' => $price
                ]]
            ]);
            
            // Then create the quote line
            $lineData = [
                'order_id' => $quoteId,
                'product_id' => $productId,
                'name' => $description,
                'product_uom_qty' => $quantity,
                'price_unit' => $price
            ];
            
            $lineId = $this->callOdoo('object', 'execute_kw', [
                $config['database'],
                $uid,
                $config['password'],
                'sale.order.line',
                'create',
                [$lineData]
            ]);
            
            return $lineId;
            
        } catch (\Exception $e) {
            Factory::getApplication()->enqueueMessage('Error creating quote line: ' . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Update a quote line
     *
     * @param   integer  $lineId      Line ID
     * @param   string   $description Description
     * @param   float    $quantity    Quantity
     * @param   float    $price       Price
     *
     * @return  boolean  Success
     */
    public function updateQuoteLine($lineId, $description, $quantity, $price)
    {
        try {
            $config = $this->getConfig();
            $uid = $this->authenticate();
            
            $result = $this->callOdoo('object', 'execute_kw', [
                $config['database'],
                $uid,
                $config['password'],
                'sale.order.line',
                'write',
                [[$lineId], [
                    'name' => $description,
                    'product_uom_qty' => $quantity,
                    'price_unit' => $price
                ]]
            ]);
            
            return $result;
            
        } catch (\Exception $e) {
            Factory::getApplication()->enqueueMessage('Error updating quote line: ' . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Delete a quote line
     *
     * @param   integer  $lineId  Line ID
     *
     * @return  boolean  Success
     */
    public function deleteQuoteLine($lineId)
    {
        try {
            $config = $this->getConfig();
            $uid = $this->authenticate();
            
            $result = $this->callOdoo('object', 'execute_kw', [
                $config['database'],
                $uid,
                $config['password'],
                'sale.order.line',
                'unlink',
                [[$lineId]]
            ]);
            
            return $result;
            
        } catch (\Exception $e) {
            Factory::getApplication()->enqueueMessage('Error deleting quote line: ' . $e->getMessage(), 'error');
            return false;
        }
    }

    /**
     * Get connection status for diagnostics
     *
     * @return  array  Connection status
     */
    public function getConnectionStatus()
    {
        try {
            $uid = $this->authenticate();
            return [
                'success' => true,
                'uid' => $uid,
                'authentication_test' => true,
                'error_message' => ''
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'uid' => null,
                'authentication_test' => false,
                'error_message' => $e->getMessage()
            ];
        }
    }

    /**
     * Get connection diagnostics
     *
     * @return  array  Detailed diagnostics
     */
    public function getConnectionDiagnostics()
    {
        $config = $this->getConfig();
        
        $diagnostics = [
            'config' => [
                'base_url' => $config['base_url'],
                'database' => $config['database'],
                'username' => $config['username'],
                'password_set' => !empty($config['password']),
                'password_length' => strlen($config['password'])
            ],
            'tests' => [],
            'errors' => [],
            'success' => false,
            'step' => 'initialization'
        ];
        
        try {
            // Test authentication
            $diagnostics['step'] = 'authentication';
            $uid = $this->authenticate();
            $diagnostics['tests']['authentication'] = true;
            $diagnostics['tests']['uid'] = $uid;
            $diagnostics['success'] = true;
            
        } catch (\Exception $e) {
            $diagnostics['tests']['authentication'] = false;
            $diagnostics['errors'][] = $e->getMessage();
            $diagnostics['success'] = false;
        }
        
        return $diagnostics;
    }
}