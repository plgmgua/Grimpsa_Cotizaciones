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

use Joomla\CMS\Factory;
use Joomla\CMS\Component\ComponentHelper;

/**
 * Helper class for Odoo integration using cURL with proper endpoints
 */
class OdooHelper
{
    /**
     * Odoo configuration
     */
    private $baseUrl;
    private $database;
    private $username;
    private $password;
    private $debug;
    private $uid = null;

    /**
     * Constructor
     */
    public function __construct()
    {
        $params = ComponentHelper::getParams('com_cotizaciones');
        
        // Extract base URL from the full URL
        $fullUrl = $params->get('odoo_url', 'https://grupoimpre.odoo.com/xmlrpc/2/object');
        $this->baseUrl = str_replace('/xmlrpc/2/object', '', $fullUrl);
        
        $this->database = $params->get('odoo_database', 'grupoimpre');
        $this->username = $params->get('odoo_username', 'admin');
        $this->password = $params->get('odoo_api_key', '2386bb5ae66c7fd9022feaf82148680c4cf4ce3b');
        $this->debug = $params->get('enable_debug', 0);
    }

    /**
     * Get comprehensive connection diagnostics
     *
     * @return  array  Detailed diagnostic information
     */
    public function getConnectionDiagnostics()
    {
        $diagnostics = [
            'step' => 'Starting diagnostics',
            'config' => [
                'base_url' => $this->baseUrl,
                'database' => $this->database,
                'username' => $this->username,
                'password_set' => !empty($this->password),
                'password_length' => strlen($this->password),
            ],
            'tests' => [],
            'errors' => [],
            'success' => false
        ];

        // Test 1: Check cURL availability
        $diagnostics['tests']['curl_available'] = function_exists('curl_init');
        if (!$diagnostics['tests']['curl_available']) {
            $diagnostics['errors'][] = 'cURL extension is not available';
            return $diagnostics;
        }

        // Test 2: Test basic connectivity to Odoo server
        $diagnostics['step'] = 'Testing basic connectivity';
        $connectTest = $this->testBasicConnectivity();
        $diagnostics['tests']['basic_connectivity'] = $connectTest['success'];
        if (!$connectTest['success']) {
            $diagnostics['errors'][] = 'Basic connectivity failed: ' . $connectTest['error'];
        }

        // Test 3: Test common endpoint
        $diagnostics['step'] = 'Testing common endpoint';
        $commonTest = $this->testCommonEndpoint();
        $diagnostics['tests']['common_endpoint'] = $commonTest['success'];
        if (!$commonTest['success']) {
            $diagnostics['errors'][] = 'Common endpoint failed: ' . $commonTest['error'];
        } else {
            $diagnostics['tests']['common_response'] = $commonTest['response'];
        }

        // Test 4: Test authentication
        $diagnostics['step'] = 'Testing authentication';
        $authTest = $this->testAuthentication();
        $diagnostics['tests']['authentication'] = $authTest['success'];
        if (!$authTest['success']) {
            $diagnostics['errors'][] = 'Authentication failed: ' . $authTest['error'];
        } else {
            $diagnostics['tests']['uid'] = $authTest['uid'];
            $this->uid = $authTest['uid'];
        }

        // Test 5: Test object endpoint (only if authenticated)
        if ($authTest['success']) {
            $diagnostics['step'] = 'Testing object endpoint';
            $objectTest = $this->testObjectEndpoint();
            $diagnostics['tests']['object_endpoint'] = $objectTest['success'];
            if (!$objectTest['success']) {
                $diagnostics['errors'][] = 'Object endpoint failed: ' . $objectTest['error'];
            } else {
                $diagnostics['tests']['object_response'] = $objectTest['response'];
            }

            // Test 6: Test actual quote search
            $diagnostics['step'] = 'Testing quote search';
            $searchTest = $this->testQuoteSearch();
            $diagnostics['tests']['quote_search'] = $searchTest['success'];
            if (!$searchTest['success']) {
                $diagnostics['errors'][] = 'Quote search failed: ' . $searchTest['error'];
            } else {
                $diagnostics['tests']['quotes_found'] = count($searchTest['quotes']);
                $diagnostics['success'] = true;
            }
        }

        $diagnostics['step'] = 'Diagnostics complete';
        return $diagnostics;
    }

    /**
     * Test basic connectivity to Odoo server
     */
    private function testBasicConnectivity()
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $this->baseUrl,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_NOBODY => true, // HEAD request only
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);

        $result = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($result === false || !empty($error)) {
            return ['success' => false, 'error' => $error ?: 'Unknown cURL error'];
        }

        if ($httpCode >= 400) {
            return ['success' => false, 'error' => "HTTP $httpCode"];
        }

        return ['success' => true, 'http_code' => $httpCode];
    }

    /**
     * Test common endpoint
     */
    private function testCommonEndpoint()
    {
        $url = $this->baseUrl . '/xmlrpc/2/common';
        
        // Simple version call
        $xmlRequest = '<?xml version="1.0"?>
<methodCall>
    <methodName>version</methodName>
    <params></params>
</methodCall>';

        $response = $this->makeCurlRequest($url, $xmlRequest);
        
        if ($response === false) {
            return ['success' => false, 'error' => 'No response from common endpoint'];
        }

        $result = $this->parseXmlResponse($response);
        
        if ($result === false) {
            return ['success' => false, 'error' => 'Invalid XML response', 'raw_response' => substr($response, 0, 500)];
        }

        return ['success' => true, 'response' => $result];
    }

    /**
     * Test authentication
     */
    private function testAuthentication()
    {
        $url = $this->baseUrl . '/xmlrpc/2/common';
        
        $xmlRequest = '<?xml version="1.0"?>
<methodCall>
    <methodName>authenticate</methodName>
    <params>
        <param><value><string>' . htmlspecialchars($this->database) . '</string></value></param>
        <param><value><string>' . htmlspecialchars($this->username) . '</string></value></param>
        <param><value><string>' . htmlspecialchars($this->password) . '</string></value></param>
        <param><value><struct></struct></value></param>
    </params>
</methodCall>';

        $response = $this->makeCurlRequest($url, $xmlRequest);
        
        if ($response === false) {
            return ['success' => false, 'error' => 'No response from authentication'];
        }

        $result = $this->parseXmlResponse($response);
        
        if ($result === false || !is_numeric($result)) {
            return ['success' => false, 'error' => 'Invalid authentication response: ' . print_r($result, true)];
        }

        return ['success' => true, 'uid' => (int) $result];
    }

    /**
     * Test object endpoint
     */
    private function testObjectEndpoint()
    {
        if (!$this->uid) {
            return ['success' => false, 'error' => 'No UID available'];
        }

        $url = $this->baseUrl . '/xmlrpc/2/object';
        
        // Test with a simple search_count call
        $xmlRequest = '<?xml version="1.0"?>
<methodCall>
    <methodName>execute_kw</methodName>
    <params>
        <param><value><string>' . htmlspecialchars($this->database) . '</string></value></param>
        <param><value><int>' . $this->uid . '</int></value></param>
        <param><value><string>' . htmlspecialchars($this->password) . '</string></value></param>
        <param><value><string>sale.order</string></value></param>
        <param><value><string>search_count</string></value></param>
        <param><value><array><data>
            <value><array><data></data></array></value>
        </data></array></value></param>
    </params>
</methodCall>';

        $response = $this->makeCurlRequest($url, $xmlRequest);
        
        if ($response === false) {
            return ['success' => false, 'error' => 'No response from object endpoint'];
        }

        $result = $this->parseXmlResponse($response);
        
        if ($result === false) {
            return ['success' => false, 'error' => 'Invalid object response', 'raw_response' => substr($response, 0, 500)];
        }

        return ['success' => true, 'response' => $result];
    }

    /**
     * Test quote search
     */
    private function testQuoteSearch()
    {
        if (!$this->uid) {
            return ['success' => false, 'error' => 'No UID available'];
        }

        $url = $this->baseUrl . '/xmlrpc/2/object';
        
        // Search for quotes with limit
        $xmlRequest = '<?xml version="1.0"?>
<methodCall>
    <methodName>execute_kw</methodName>
    <params>
        <param><value><string>' . htmlspecialchars($this->database) . '</string></value></param>
        <param><value><int>' . $this->uid . '</int></value></param>
        <param><value><string>' . htmlspecialchars($this->password) . '</string></value></param>
        <param><value><string>sale.order</string></value></param>
        <param><value><string>search</string></value></param>
        <param><value><array><data>
            <value><array><data></data></array></value>
            <value><struct>
                <member><name>limit</name><value><int>5</int></value></member>
            </struct></value>
        </data></array></value></param>
    </params>
</methodCall>';

        $response = $this->makeCurlRequest($url, $xmlRequest);
        
        if ($response === false) {
            return ['success' => false, 'error' => 'No response from quote search'];
        }

        $result = $this->parseXmlResponse($response);
        
        if ($result === false) {
            return ['success' => false, 'error' => 'Invalid search response'];
        }

        return ['success' => true, 'quotes' => is_array($result) ? $result : []];
    }

    /**
     * Authenticate with Odoo and get UID
     *
     * @return  boolean  True if authentication successful
     */
    private function authenticate()
    {
        if ($this->uid !== null) {
            return true; // Already authenticated
        }

        try {
            $url = $this->baseUrl . '/xmlrpc/2/common';
            
            $xmlRequest = '<?xml version="1.0"?>
<methodCall>
    <methodName>authenticate</methodName>
    <params>
        <param><value><string>' . htmlspecialchars($this->database) . '</string></value></param>
        <param><value><string>' . htmlspecialchars($this->username) . '</string></value></param>
        <param><value><string>' . htmlspecialchars($this->password) . '</string></value></param>
        <param><value><struct></struct></value></param>
    </params>
</methodCall>';

            $response = $this->makeCurlRequest($url, $xmlRequest);
            
            if ($response === false) {
                if ($this->debug) {
                    Factory::getApplication()->enqueueMessage('Authentication failed: No response from server', 'error');
                }
                return false;
            }

            $result = $this->parseXmlResponse($response);
            
            if ($result === false || !is_numeric($result)) {
                if ($this->debug) {
                    Factory::getApplication()->enqueueMessage('Authentication failed: Invalid response - ' . print_r($result, true), 'error');
                }
                return false;
            }

            $this->uid = (int) $result;
            
            if ($this->debug) {
                Factory::getApplication()->enqueueMessage('Authentication successful. UID: ' . $this->uid, 'info');
            }
            
            return true;

        } catch (Exception $e) {
            if ($this->debug) {
                Factory::getApplication()->enqueueMessage('Authentication error: ' . $e->getMessage(), 'error');
            }
            return false;
        }
    }

    /**
     * Make cURL request to Odoo
     *
     * @param   string  $url     The URL to call
     * @param   string  $xmlData The XML data to send
     *
     * @return  string|false  Response or false on error
     */
    private function makeCurlRequest($url, $xmlData)
    {
        $ch = curl_init();
        
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => $xmlData,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_HTTPHEADER => [
                'Content-Type: text/xml',
                'Content-Length: ' . strlen($xmlData)
            ],
            CURLOPT_SSL_VERIFYPEER => false,
            CURLOPT_SSL_VERIFYHOST => false,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error = curl_error($ch);
        curl_close($ch);

        if ($response === false || !empty($error)) {
            if ($this->debug) {
                Factory::getApplication()->enqueueMessage('cURL Error: ' . $error, 'error');
            }
            return false;
        }

        if ($httpCode !== 200) {
            if ($this->debug) {
                Factory::getApplication()->enqueueMessage('HTTP Error: ' . $httpCode . ' - Response: ' . substr($response, 0, 500), 'error');
            }
            return false;
        }

        return $response;
    }

    /**
     * Make XML-RPC call to Odoo
     *
     * @param   string  $model   The Odoo model
     * @param   string  $method  The method to call
     * @param   array   $args    Arguments for the method
     *
     * @return  mixed   The result or false on error
     */
    private function odooCall($model, $method, $args = [])
    {
        if (!$this->authenticate()) {
            return false;
        }

        try {
            $url = $this->baseUrl . '/xmlrpc/2/object';
            
            $xmlRequest = '<?xml version="1.0"?>
<methodCall>
    <methodName>execute_kw</methodName>
    <params>
        <param><value><string>' . htmlspecialchars($this->database) . '</string></value></param>
        <param><value><int>' . $this->uid . '</int></value></param>
        <param><value><string>' . htmlspecialchars($this->password) . '</string></value></param>
        <param><value><string>' . htmlspecialchars($model) . '</string></value></param>
        <param><value><string>' . htmlspecialchars($method) . '</string></value></param>
        <param><value><array><data>';

            // Add arguments
            foreach ($args as $arg) {
                $xmlRequest .= '<value>' . $this->encodeValue($arg) . '</value>';
            }

            $xmlRequest .= '</data></array></value></param>
    </params>
</methodCall>';

            $response = $this->makeCurlRequest($url, $xmlRequest);
            
            if ($response === false) {
                return false;
            }

            $result = $this->parseXmlResponse($response);
            
            if ($this->debug && $method === 'search') {
                Factory::getApplication()->enqueueMessage('Odoo call result for ' . $model . '.' . $method . ': ' . print_r($result, true), 'info');
            }
            
            return $result;

        } catch (Exception $e) {
            if ($this->debug) {
                Factory::getApplication()->enqueueMessage('Odoo Call Error: ' . $e->getMessage(), 'error');
            }
            return false;
        }
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
        if (is_array($value)) {
            if (empty($value)) {
                return '<array><data></data></array>';
            }
            
            // Check if it's an associative array (struct) or indexed array
            if (array_keys($value) !== range(0, count($value) - 1)) {
                // Associative array - encode as struct
                $xml = '<struct>';
                foreach ($value as $key => $val) {
                    $xml .= '<member><name>' . htmlspecialchars($key) . '</name><value>' . $this->encodeValue($val) . '</value></member>';
                }
                $xml .= '</struct>';
                return $xml;
            } else {
                // Indexed array
                $xml = '<array><data>';
                foreach ($value as $val) {
                    $xml .= '<value>' . $this->encodeValue($val) . '</value>';
                }
                $xml .= '</data></array>';
                return $xml;
            }
        } elseif (is_int($value)) {
            return '<int>' . $value . '</int>';
        } elseif (is_float($value)) {
            return '<double>' . $value . '</double>';
        } elseif (is_bool($value)) {
            return '<boolean>' . ($value ? '1' : '0') . '</boolean>';
        } else {
            return '<string>' . htmlspecialchars((string)$value) . '</string>';
        }
    }

    /**
     * Parse XML-RPC response
     *
     * @param   string  $xml  The XML response
     *
     * @return  mixed   The parsed result or false on error
     */
    private function parseXmlResponse($xml)
    {
        try {
            $dom = new \DOMDocument();
            $dom->loadXML($xml);
            
            // Check for fault
            $fault = $dom->getElementsByTagName('fault');
            if ($fault->length > 0) {
                if ($this->debug) {
                    $faultValue = $fault->item(0)->getElementsByTagName('value')->item(0);
                    $faultData = $this->parseValue($faultValue);
                    Factory::getApplication()->enqueueMessage('Odoo fault: ' . print_r($faultData, true), 'error');
                }
                return false;
            }

            // Get the response value
            $params = $dom->getElementsByTagName('param');
            if ($params->length === 0) {
                return false;
            }

            $value = $params->item(0)->getElementsByTagName('value')->item(0);
            return $this->parseValue($value);

        } catch (Exception $e) {
            if ($this->debug) {
                Factory::getApplication()->enqueueMessage('XML Parse Error: ' . $e->getMessage(), 'error');
            }
            return false;
        }
    }

    /**
     * Parse a value from XML
     *
     * @param   \DOMElement  $element  The XML element
     *
     * @return  mixed  The parsed value
     */
    private function parseValue($element)
    {
        if (!$element) {
            return null;
        }

        $firstChild = $element->firstChild;
        if (!$firstChild) {
            return $element->textContent;
        }

        switch ($firstChild->nodeName) {
            case 'array':
                $result = [];
                $data = $firstChild->getElementsByTagName('data')->item(0);
                if ($data) {
                    $values = $data->getElementsByTagName('value');
                    for ($i = 0; $i < $values->length; $i++) {
                        $result[] = $this->parseValue($values->item($i));
                    }
                }
                return $result;

            case 'struct':
                $result = [];
                $members = $firstChild->getElementsByTagName('member');
                for ($i = 0; $i < $members->length; $i++) {
                    $member = $members->item($i);
                    $name = $member->getElementsByTagName('name')->item(0)->textContent;
                    $value = $member->getElementsByTagName('value')->item(0);
                    $result[$name] = $this->parseValue($value);
                }
                return $result;

            case 'int':
            case 'i4':
                return (int) $firstChild->textContent;

            case 'double':
                return (float) $firstChild->textContent;

            case 'boolean':
                return $firstChild->textContent === '1';

            case 'string':
            default:
                return $firstChild->textContent;
        }
    }

    /**
     * Get quotes by sales agent
     *
     * @param   string   $agentName  The sales agent name
     * @param   integer  $page       Page number
     * @param   integer  $limit      Items per page
     * @param   string   $search     Search term
     *
     * @return  array    Array of quotes
     */
    public function getQuotesByAgent($agentName, $page = 1, $limit = 20, $search = '')
    {
        try {
            // Build domain filters
            $domain = [
                ['x_studio_agente_de_ventas_1', '=', $agentName]
            ];

            // Add search filter if provided
            if (!empty($search)) {
                $domain[] = ['partner_id.name', 'ilike', $search];
            }

            // Calculate offset
            $offset = ($page - 1) * $limit;

            // Get quote IDs first
            $quoteIds = $this->odooCall('sale.order', 'search', [
                $domain,
                ['offset' => $offset, 'limit' => $limit, 'order' => 'date_order desc']
            ]);

            if (!$quoteIds || !is_array($quoteIds)) {
                return $this->getMockQuotes($search);
            }

            // Get quote details
            $quotes = $this->odooCall('sale.order', 'read', [
                $quoteIds,
                ['id', 'name', 'partner_id', 'date_order', 'amount_total', 'state', 'note']
            ]);

            if (!$quotes || !is_array($quotes)) {
                return $this->getMockQuotes($search);
            }

            // Process the quotes to add contact names
            $processedQuotes = [];
            foreach ($quotes as $quote) {
                $processedQuote = [
                    'id' => $quote['id'],
                    'name' => $quote['name'],
                    'partner_id' => is_array($quote['partner_id']) ? $quote['partner_id'][0] : $quote['partner_id'],
                    'contact_name' => is_array($quote['partner_id']) ? $quote['partner_id'][1] : 'Cliente no disponible',
                    'date_order' => $quote['date_order'],
                    'amount_total' => $quote['amount_total'],
                    'state' => $quote['state'],
                    'note' => $quote['note'] ?: ''
                ];
                $processedQuotes[] = $processedQuote;
            }

            return $processedQuotes;

        } catch (Exception $e) {
            if ($this->debug) {
                Factory::getApplication()->enqueueMessage('Error getting quotes: ' . $e->getMessage(), 'error');
            }
            
            // Return mock data as fallback
            return $this->getMockQuotes($search);
        }
    }

    /**
     * Get a single quote
     *
     * @param   integer  $quoteId  The quote ID
     *
     * @return  array|false  Quote data or false
     */
    public function getQuote($quoteId)
    {
        try {
            $quotes = $this->odooCall('sale.order', 'read', [
                [$quoteId],
                ['id', 'name', 'partner_id', 'date_order', 'amount_total', 'state', 'note']
            ]);

            if (!$quotes || !is_array($quotes) || empty($quotes)) {
                return false;
            }

            $quote = $quotes[0];
            
            return [
                'id' => $quote['id'],
                'name' => $quote['name'],
                'partner_id' => is_array($quote['partner_id']) ? $quote['partner_id'][0] : $quote['partner_id'],
                'contact_name' => is_array($quote['partner_id']) ? $quote['partner_id'][1] : 'Cliente no disponible',
                'date_order' => $quote['date_order'],
                'amount_total' => $quote['amount_total'],
                'state' => $quote['state'],
                'note' => $quote['note'] ?: ''
            ];

        } catch (Exception $e) {
            if ($this->debug) {
                Factory::getApplication()->enqueueMessage('Error getting quote: ' . $e->getMessage(), 'error');
            }
            return false;
        }
    }

    /**
     * Create a new quote
     *
     * @param   array  $data  Quote data
     *
     * @return  integer|false  New quote ID or false
     */
    public function createQuote($data)
    {
        try {
            $quoteData = [
                'partner_id' => (int) $data['partner_id'],
                'date_order' => $data['date_order'],
                'note' => $data['note'] ?: '',
                'x_studio_agente_de_ventas_1' => $data['x_studio_agente_de_ventas_1']
            ];

            $quoteId = $this->odooCall('sale.order', 'create', [$quoteData]);

            if ($quoteId && is_numeric($quoteId)) {
                return (int) $quoteId;
            }

            return false;

        } catch (Exception $e) {
            if ($this->debug) {
                Factory::getApplication()->enqueueMessage('Error creating quote: ' . $e->getMessage(), 'error');
            }
            return false;
        }
    }

    /**
     * Update a quote
     *
     * @param   integer  $quoteId  Quote ID
     * @param   array    $data     Quote data
     *
     * @return  boolean  Success status
     */
    public function updateQuote($quoteId, $data)
    {
        try {
            $updateData = [];
            
            if (isset($data['partner_id'])) {
                $updateData['partner_id'] = (int) $data['partner_id'];
            }
            
            if (isset($data['date_order'])) {
                $updateData['date_order'] = $data['date_order'];
            }
            
            if (isset($data['note'])) {
                $updateData['note'] = $data['note'];
            }

            if (empty($updateData)) {
                return true; // Nothing to update
            }

            $result = $this->odooCall('sale.order', 'write', [[$quoteId], $updateData]);

            return $result === true;

        } catch (Exception $e) {
            if ($this->debug) {
                Factory::getApplication()->enqueueMessage('Error updating quote: ' . $e->getMessage(), 'error');
            }
            return false;
        }
    }

    /**
     * Delete a quote
     *
     * @param   integer  $quoteId  Quote ID
     *
     * @return  boolean  Success status
     */
    public function deleteQuote($quoteId)
    {
        try {
            $result = $this->odooCall('sale.order', 'unlink', [[$quoteId]]);
            return $result === true;

        } catch (Exception $e) {
            if ($this->debug) {
                Factory::getApplication()->enqueueMessage('Error deleting quote: ' . $e->getMessage(), 'error');
            }
            return false;
        }
    }

    /**
     * Get mock quotes as fallback
     *
     * @param   string  $search  Search term
     *
     * @return  array   Array of mock quotes
     */
    private function getMockQuotes($search = '')
    {
        $mockQuotes = [
            [
                'id' => 1,
                'name' => 'SO001',
                'partner_id' => 123,
                'contact_name' => 'Cliente Ejemplo 1',
                'date_order' => date('Y-m-d'),
                'amount_total' => '1500.00',
                'state' => 'draft',
                'note' => 'Cotización de ejemplo (modo offline)'
            ],
            [
                'id' => 2,
                'name' => 'SO002',
                'partner_id' => 124,
                'contact_name' => 'Cliente Ejemplo 2',
                'date_order' => date('Y-m-d', strtotime('-1 day')),
                'amount_total' => '2500.00',
                'state' => 'sent',
                'note' => 'Segunda cotización (modo offline)'
            ]
        ];

        // Filter by search if provided
        if (!empty($search)) {
            $mockQuotes = array_filter($mockQuotes, function($quote) use ($search) {
                return stripos($quote['contact_name'], $search) !== false;
            });
        }

        return array_values($mockQuotes);
    }

    /**
     * Test Odoo connection
     *
     * @return  boolean  True if connection works
     */
    public function testConnection()
    {
        return $this->authenticate();
    }

    /**
     * Get detailed connection status for debugging
     *
     * @return  array  Connection status details
     */
    public function getConnectionStatus()
    {
        $status = [
            'base_url' => $this->baseUrl,
            'database' => $this->database,
            'username' => $this->username,
            'password_set' => !empty($this->password),
            'curl_available' => function_exists('curl_init'),
            'connection_test' => false,
            'authentication_test' => false,
            'uid' => null,
            'error_message' => ''
        ];

        try {
            // Test basic cURL functionality
            $ch = curl_init();
            if (!$ch) {
                $status['error_message'] = 'cURL initialization failed';
                return $status;
            }
            curl_close($ch);

            // Test authentication
            $authResult = $this->authenticate();
            $status['authentication_test'] = $authResult;
            $status['uid'] = $this->uid;
            
            if ($authResult) {
                $status['connection_test'] = true;
            } else {
                $status['error_message'] = 'Authentication failed - check username/password';
            }

        } catch (Exception $e) {
            $status['error_message'] = $e->getMessage();
        }

        return $status;
    }
}