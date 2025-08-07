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
            'base_url' => $params->get('odoo_url', 'https://grupoimpre.odoo.com/xmlrpc/2/object'),
            'database' => $params->get('odoo_database', 'grupoimpre'),
            'user_id' => $params->get('odoo_user_id', '2'),
            'username' => $params->get('odoo_username', 'admin'),
            'password' => $params->get('odoo_api_key', ''),
            'timeout' => $params->get('connection_timeout', 30),
            'debug' => $params->get('enable_debug', 0)
        ];
    }

    /**
     * Make XML-RPC call to Odoo using cURL
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
        
        // Build XML-RPC request manually
        $xml = '<?xml version="1.0"?>';
        $xml .= '<methodCall>';
        $xml .= '<methodName>' . htmlspecialchars($method) . '</methodName>';
        $xml .= '<params>';
        
        foreach ($params as $param) {
            $xml .= '<param>';
            $xml .= $this->encodeValue($param);
            $xml .= '</param>';
        }
        
        $xml .= '</params>';
        $xml .= '</methodCall>';
        
        // Use cURL for the request
        $ch = curl_init();
        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, $xml);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_TIMEOUT, $config['timeout']);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: text/xml',
            'Content-Length: ' . strlen($xml)
        ]);
        
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        
        if (curl_error($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            throw new \Exception('cURL error: ' . $error);
        }
        
        curl_close($ch);
        
        if ($httpCode !== 200) {
            throw new \Exception('HTTP error: ' . $httpCode);
        }
        
        if ($response === false) {
            throw new \Exception('Failed to connect to Odoo server');
        }
        
        // Parse XML response
        $result = $this->parseXmlResponse($response);
        
        return $result;
    }

    /**
     * Encode a value for XML-RPC
     *
     * @param   mixed  $value  The value to encode
     *
     * @return  string  The encoded XML
     */
    private function encodeValue($value)
    {
        if (is_string($value)) {
            return '<value><string>' . htmlspecialchars($value) . '</string></value>';
        } elseif (is_int($value)) {
            return '<value><int>' . $value . '</int></value>';
        } elseif (is_float($value)) {
            return '<value><double>' . $value . '</double></value>';
        } elseif (is_bool($value)) {
            return '<value><boolean>' . ($value ? '1' : '0') . '</boolean></value>';
        } elseif (is_array($value)) {
            if ($this->isAssociativeArray($value)) {
                // Struct
                $xml = '<value><struct>';
                foreach ($value as $key => $val) {
                    $xml .= '<member>';
                    $xml .= '<name>' . htmlspecialchars($key) . '</name>';
                    $xml .= $this->encodeValue($val);
                    $xml .= '</member>';
                }
                $xml .= '</struct></value>';
                return $xml;
            } else {
                // Array
                $xml = '<value><array><data>';
                foreach ($value as $val) {
                    $xml .= $this->encodeValue($val);
                }
                $xml .= '</data></array></value>';
                return $xml;
            }
        }
        
        return '<value><string></string></value>';
    }

    /**
     * Check if array is associative
     *
     * @param   array  $array  The array to check
     *
     * @return  boolean  True if associative
     */
    private function isAssociativeArray($array)
    {
        return array_keys($array) !== range(0, count($array) - 1);
    }

    /**
     * Parse XML-RPC response
     *
     * @param   string  $xml  The XML response
     *
     * @return  mixed   The parsed value
     */
    private function parseXmlResponse($xml)
    {
        $dom = new \DOMDocument();
        $dom->loadXML($xml);
        
        // Check for fault
        $fault = $dom->getElementsByTagName('fault');
        if ($fault->length > 0) {
            $faultValue = $fault->item(0)->getElementsByTagName('value')->item(0);
            throw new \Exception('Odoo fault: ' . $this->parseValue($faultValue));
        }
        
        // Get the return value
        $params = $dom->getElementsByTagName('param');
        if ($params->length > 0) {
            $value = $params->item(0)->getElementsByTagName('value')->item(0);
            return $this->parseValue($value);
        }
        
        return null;
    }

    /**
     * Parse XML value
     *
     * @param   DOMElement  $element  The XML element
     *
     * @return  mixed  The parsed value
     */
    private function parseValue($element)
    {
        $child = $element->firstChild;
        
        if (!$child) {
            return '';
        }
        
        switch ($child->nodeName) {
            case 'string':
                return $child->textContent;
            case 'int':
            case 'i4':
                return (int) $child->textContent;
            case 'double':
                return (float) $child->textContent;
            case 'boolean':
                return $child->textContent === '1';
            case 'array':
                $result = [];
                $data = $child->getElementsByTagName('data')->item(0);
                $values = $data->getElementsByTagName('value');
                for ($i = 0; $i < $values->length; $i++) {
                    $result[] = $this->parseValue($values->item($i));
                }
                return $result;
            case 'struct':
                $result = [];
                $members = $child->getElementsByTagName('member');
                for ($i = 0; $i < $members->length; $i++) {
                    $member = $members->item($i);
                    $name = $member->getElementsByTagName('name')->item(0)->textContent;
                    $value = $member->getElementsByTagName('value')->item(0);
                    $result[$name] = $this->parseValue($value);
                }
                return $result;
            default:
                return $child->textContent;
        }
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
        
        $uid = $this->callOdoo('common', 'authenticate', [
            $config['database'],
            $config['username'],
            $config['password'],
            []
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
            $uid = $this->authenticate();
            $config = $this->getConfig();
            
            // Build domain
            $domain = [
                ['x_studio_agente_de_ventas_1', '=', $agentName]
            ];
            
            // Add search filter
            if (!empty($search)) {
                $domain[] = ['partner_id', 'ilike', trim($search)];
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
            $uid = $this->authenticate();
            $config = $this->getConfig();
            
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
            $uid = $this->authenticate();
            $config = $this->getConfig();
            
            // Search for clients with case-insensitive partial match
            $domain = [
                ['name', 'ilike', trim($searchTerm)],
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
            $uid = $this->authenticate();
            $config = $this->getConfig();
            
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
            $uid = $this->authenticate();
            $config = $this->getConfig();
            
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
            $uid = $this->authenticate();
            $config = $this->getConfig();
            
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
            $uid = $this->authenticate();
            $config = $this->getConfig();
            
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
            $uid = $this->authenticate();
            $config = $this->getConfig();
            
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
            $uid = $this->authenticate();
            $config = $this->getConfig();
            
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
            $uid = $this->authenticate();
            $config = $this->getConfig();
            
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
            
            // Test a simple call to verify connection
            $config = $this->getConfig();
            $this->callOdoo('object', 'execute_kw', [
                $config['database'],
                $uid,
                $config['password'],
                'sale.order',
                'search',
                [[]],
                ['limit' => 1]
            ]);
            
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
                'user_id' => $config['user_id'],
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
            
            // Test a simple call
            $diagnostics['step'] = 'quote_search';
            $config = $this->getConfig();
            $this->callOdoo('object', 'execute_kw', [
                $config['database'],
                $uid,
                $config['password'],
                'sale.order',
                'search',
                [[]],
                ['limit' => 1]
            ]);
            
            $diagnostics['tests']['quote_search'] = true;
            $diagnostics['success'] = true;
            
        } catch (\Exception $e) {
            $diagnostics['tests']['authentication'] = false;
            $diagnostics['errors'][] = $e->getMessage();
            $diagnostics['success'] = false;
        }
        
        return $diagnostics;
    }
}