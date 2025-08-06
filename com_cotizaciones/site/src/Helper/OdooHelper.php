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
 * Helper class for Odoo integration using the exact working XML-RPC format
 */
class OdooHelper
{
    /**
     * Odoo configuration
     */
    private $url;
    private $database;
    private $userId;
    private $apiKey;
    private $debug;

    /**
     * Constructor
     */
    public function __construct()
    {
        $params = ComponentHelper::getParams('com_cotizaciones');
        
        $this->url = $params->get('odoo_url', 'https://grupoimpre.odoo.com/xmlrpc/2/object');
        $this->database = $params->get('odoo_database', 'grupoimpre');
        $this->userId = (int) $params->get('odoo_user_id', 2);
        $this->apiKey = $params->get('odoo_api_key', '2386bb5ae66c7fd9022feaf82148680c4cf4ce3b');
        $this->debug = $params->get('enable_debug', 0);
    }

    /**
     * Get quotes by sales agent using the working XML-RPC format
     *
     * @param   string   $agentName  The sales agent name
     * @param   integer  $page       Page number
     * @param   integer  $limit      Items per page
     * @param   string   $search     Search term
     * @param   string   $stateFilter State filter
     *
     * @return  array    Array of quotes
     */
    public function getQuotesByAgent($agentName, $page = 1, $limit = 20, $search = '', $stateFilter = '')
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

            // Add state filter if provided
            if (!empty($stateFilter)) {
                $domain[] = ['state', '=', $stateFilter];
            }

            // Calculate offset
            $offset = ($page - 1) * $limit;

            // Use search_read method like in the working Postman example
            $quotes = $this->odooCall('sale.order', 'search_read', $domain, 
                ['id', 'name', 'partner_id', 'date_order', 'amount_total', 'state', 'note'],
                ['offset' => $offset, 'limit' => $limit, 'order' => 'date_order desc']
            );

            if ($quotes === false || !is_array($quotes)) {
                if ($this->debug) {
                    Factory::getApplication()->enqueueMessage('No quotes returned from Odoo or invalid response format', 'warning');
                }
                return [];
            }

            // Process the quotes to add contact names
            $processedQuotes = [];
            foreach ($quotes as $quote) {
                // Ensure $quote is an array before accessing
                if (!is_array($quote)) {
                    if ($this->debug) {
                        Factory::getApplication()->enqueueMessage('Invalid quote format: ' . print_r($quote, true), 'warning');
                    }
                    continue;
                }

                // Filter out quotes without valid numbers
                $quoteName = isset($quote['name']) ? trim($quote['name']) : '';
                if (!$this->isValidQuoteNumber($quoteName)) {
                    if ($this->debug) {
                        Factory::getApplication()->enqueueMessage('Skipping quote without valid number: ' . $quoteName, 'info');
                    }
                    continue;
                }

                $processedQuote = [
                    'id' => isset($quote['id']) ? $quote['id'] : 0,
                    'name' => isset($quote['name']) ? $quote['name'] : 'Sin número',
                    'partner_id' => $this->getPartnerId($quote),
                    'contact_name' => $this->getContactName($quote),
                    'date_order' => isset($quote['date_order']) ? $quote['date_order'] : date('Y-m-d'),
                    'amount_total' => isset($quote['amount_total']) ? $quote['amount_total'] : '0.00',
                    'state' => isset($quote['state']) ? $quote['state'] : 'draft',
                    'note' => isset($quote['note']) ? $quote['note'] : ''
                ];
                $processedQuotes[] = $processedQuote;
            }

            // Sort quotes by quote number (descending - newest first)
            usort($processedQuotes, function($a, $b) {
                $numA = $this->extractQuoteNumber($a['name']);
                $numB = $this->extractQuoteNumber($b['name']);
                
                // Debug the numbers being compared
                if ($this->debug) {
                    Factory::getApplication()->enqueueMessage("Comparing: {$a['name']} ($numA) vs {$b['name']} ($numB)", 'info');
                }
                
                // Ensure proper integer comparison for descending order
                if ($numB == $numA) {
                    return 0;
                }
                return ($numB > $numA) ? 1 : -1; // Descending order (highest first)
            });

            return $processedQuotes;

        } catch (Exception $e) {
            if ($this->debug) {
                Factory::getApplication()->enqueueMessage('Error getting quotes: ' . $e->getMessage(), 'error');
            }
            
            return [];
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
            $quotes = $this->odooCall('sale.order', 'search_read', 
                [['id', '=', $quoteId]],
                ['id', 'name', 'partner_id', 'date_order', 'amount_total', 'state', 'note']
            );

            if ($quotes === false || !is_array($quotes) || empty($quotes)) {
                return false;
            }

            $quote = $quotes[0];
            
            // Ensure $quote is an array
            if (!is_array($quote)) {
                return false;
            }
            
            return [
                'id' => isset($quote['id']) ? $quote['id'] : 0,
                'name' => isset($quote['name']) ? $quote['name'] : 'Sin número',
                'partner_id' => $this->getPartnerId($quote),
                'contact_name' => $this->getContactName($quote),
                'date_order' => isset($quote['date_order']) ? $quote['date_order'] : date('Y-m-d'),
                'amount_total' => isset($quote['amount_total']) ? $quote['amount_total'] : '0.00',
                'state' => isset($quote['state']) ? $quote['state'] : 'draft',
                'note' => isset($quote['note']) ? $quote['note'] : ''
            ];

        } catch (Exception $e) {
            if ($this->debug) {
                Factory::getApplication()->enqueueMessage('Error getting quote: ' . $e->getMessage(), 'error');
            }
            return false;
        }
    }

    /**
     * Get list of clients/partners from Odoo
     *
     * @param   string  $search  Search term for client name
     *
     * @return  array   Array of clients
     */
    public function getClients($search = '')
    {
        try {
            $domain = [['is_company', '=', true]];
            
            if (!empty($search)) {
                $domain[] = ['name', 'ilike', $search];
            }
            
            $clients = $this->odooCall('res.partner', 'search_read', $domain,
                ['id', 'name', 'email', 'phone'],
                ['limit' => 50, 'order' => 'name asc']
            );
            
            if ($clients === false || !is_array($clients)) {
                return [];
            }
            
            return $clients;
            
        } catch (Exception $e) {
            if ($this->debug) {
                Factory::getApplication()->enqueueMessage('Error getting clients: ' . $e->getMessage(), 'error');
            }
            return [];
        }
    }

    /**
     * Create a quote line in Odoo
     *
     * @param   integer  $quoteId      The quote ID
     * @param   string   $productName  Product name (incremental)
     * @param   string   $description  Product description
     * @param   float    $quantity     Quantity
     * @param   float    $price        Unit price
     *
     * @return  integer|false  Line ID or false
     */
    public function createQuoteLine($quoteId, $productName, $description, $quantity, $price)
    {
        try {
            // First, create or get the product
            $productId = $this->getOrCreateProduct($productName, $description, $price);
            
            if (!$productId) {
                return false;
            }
            
            // Create the quote line
            $lineData = [
                'order_id' => (int) $quoteId,
                'product_id' => $productId,
                'name' => $description,
                'product_uom_qty' => (float) $quantity,
                'price_unit' => (float) $price
            ];
            
            $xmlRequest = '<?xml version="1.0"?>
<methodCall>
   <methodName>execute_kw</methodName>
   <params>
      <param>
         <value><string>' . htmlspecialchars($this->database) . '</string></value>
      </param>
      <param>
         <value><int>' . $this->userId . '</int></value>
      </param>
      <param>
         <value><string>' . htmlspecialchars($this->apiKey) . '</string></value>
      </param>
      <param>
         <value><string>sale.order.line</string></value>
      </param>
      <param>
         <value><string>create</string></value>
      </param>
      <param>
         <value><array><data>
            <value><struct>';

            foreach ($lineData as $key => $value) {
                $xmlRequest .= '<member><name>' . htmlspecialchars($key) . '</name>';
                if (is_int($value)) {
                    $xmlRequest .= '<value><int>' . $value . '</int></value>';
                } elseif (is_float($value)) {
                    $xmlRequest .= '<value><double>' . $value . '</double></value>';
                } else {
                    $xmlRequest .= '<value><string>' . htmlspecialchars($value) . '</string></value>';
                }
                $xmlRequest .= '</member>';
            }

            $xmlRequest .= '</struct></value>
         </data></array></value>
      </param>
   </params>
</methodCall>';

            $response = $this->makeCurlRequest($this->url, $xmlRequest);
            
            if ($response === false) {
                return false;
            }

            $result = $this->parseXmlResponse($response);

            if ($result && is_numeric($result)) {
                return (int) $result;
            }

            return false;

        } catch (Exception $e) {
            if ($this->debug) {
                Factory::getApplication()->enqueueMessage('Error creating quote line: ' . $e->getMessage(), 'error');
            }
            return false;
        }
    }

    /**
     * Get or create a product with incremental naming
     *
     * @param   string  $productName   Base product name
     * @param   string  $description   Product description
     * @param   float   $price         Default price
     *
     * @return  integer|false  Product ID or false
     */
    private function getOrCreateProduct($productName, $description, $price)
    {
        try {
            // Search for existing product with this name
            $products = $this->odooCall('product.product', 'search_read',
                [['name', '=', $productName]],
                ['id', 'name']
            );
            
            if ($products && is_array($products) && count($products) > 0) {
                return (int) $products[0]['id'];
            }
            
            // Create new product
            $productData = [
                'name' => $productName,
                'description' => $description,
                'list_price' => (float) $price,
                'type' => 'service',
                'sale_ok' => true
            ];
            
            $xmlRequest = '<?xml version="1.0"?>
<methodCall>
   <methodName>execute_kw</methodName>
   <params>
      <param>
         <value><string>' . htmlspecialchars($this->database) . '</string></value>
      </param>
      <param>
         <value><int>' . $this->userId . '</int></value>
      </param>
      <param>
         <value><string>' . htmlspecialchars($this->apiKey) . '</string></value>
      </param>
      <param>
         <value><string>product.product</string></value>
      </param>
      <param>
         <value><string>create</string></value>
      </param>
      <param>
         <value><array><data>
            <value><struct>';

            foreach ($productData as $key => $value) {
                $xmlRequest .= '<member><name>' . htmlspecialchars($key) . '</name>';
                if (is_bool($value)) {
                    $xmlRequest .= '<value><boolean>' . ($value ? '1' : '0') . '</boolean></value>';
                } elseif (is_float($value)) {
                    $xmlRequest .= '<value><double>' . $value . '</double></value>';
                } else {
                    $xmlRequest .= '<value><string>' . htmlspecialchars($value) . '</string></value>';
                }
                $xmlRequest .= '</member>';
            }

            $xmlRequest .= '</struct></value>
         </data></array></value>
      </param>
   </params>
</methodCall>';

            $response = $this->makeCurlRequest($this->url, $xmlRequest);
            
            if ($response === false) {
                return false;
            }

            $result = $this->parseXmlResponse($response);

            if ($result && is_numeric($result)) {
                return (int) $result;
            }

            return false;

        } catch (Exception $e) {
            if ($this->debug) {
                Factory::getApplication()->enqueueMessage('Error creating product: ' . $e->getMessage(), 'error');
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

            // Use create method
            $xmlRequest = '<?xml version="1.0"?>
<methodCall>
   <methodName>execute_kw</methodName>
   <params>
      <param>
         <value><string>' . htmlspecialchars($this->database) . '</string></value>
      </param>
      <param>
         <value><int>' . $this->userId . '</int></value>
      </param>
      <param>
         <value><string>' . htmlspecialchars($this->apiKey) . '</string></value>
      </param>
      <param>
         <value><string>sale.order</string></value>
      </param>
      <param>
         <value><string>create</string></value>
      </param>
      <param>
         <value><array><data>
            <value><struct>';

            foreach ($quoteData as $key => $value) {
                $xmlRequest .= '<member><name>' . htmlspecialchars($key) . '</name>';
                if (is_int($value)) {
                    $xmlRequest .= '<value><int>' . $value . '</int></value>';
                } else {
                    $xmlRequest .= '<value><string>' . htmlspecialchars($value) . '</string></value>';
                }
                $xmlRequest .= '</member>';
            }

            $xmlRequest .= '</struct></value>
         </data></array></value>
      </param>
   </params>
</methodCall>';

            $response = $this->makeCurlRequest($this->url, $xmlRequest);
            
            if ($response === false) {
                return false;
            }

            $result = $this->parseXmlResponse($response);

            if ($result && is_numeric($result)) {
                return (int) $result;
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

            // Use write method
            $xmlRequest = '<?xml version="1.0"?>
<methodCall>
   <methodName>execute_kw</methodName>
   <params>
      <param>
         <value><string>' . htmlspecialchars($this->database) . '</string></value>
      </param>
      <param>
         <value><int>' . $this->userId . '</int></value>
      </param>
      <param>
         <value><string>' . htmlspecialchars($this->apiKey) . '</string></value>
      </param>
      <param>
         <value><string>sale.order</string></value>
      </param>
      <param>
         <value><string>write</string></value>
      </param>
      <param>
         <value><array><data>
            <value><array><data>
               <value><int>' . $quoteId . '</int></value>
            </data></array></value>
            <value><struct>';

            foreach ($updateData as $key => $value) {
                $xmlRequest .= '<member><name>' . htmlspecialchars($key) . '</name>';
                if (is_int($value)) {
                    $xmlRequest .= '<value><int>' . $value . '</int></value>';
                } else {
                    $xmlRequest .= '<value><string>' . htmlspecialchars($value) . '</string></value>';
                }
                $xmlRequest .= '</member>';
            }

            $xmlRequest .= '</struct></value>
         </data></array></value>
      </param>
   </params>
</methodCall>';

            $response = $this->makeCurlRequest($this->url, $xmlRequest);
            
            if ($response === false) {
                return false;
            }

            $result = $this->parseXmlResponse($response);

            return $result !== false;

        } catch (Exception $e) {
            if ($this->debug) {
                Factory::getApplication()->enqueueMessage('Error updating quote: ' . $e->getMessage(), 'error');
            }
            return false;
        }
    }

    /**
     * Test Odoo connection
     *
     * @return  boolean  True if connection works
     */
    public function testConnection()
    {
        $test = $this->testOdooApiCall();
        return $test['success'];
    }

    /**
     * Get detailed connection status for debugging
     *
     * @return  array  Connection status details
     */
    public function getConnectionStatus()
    {
        $status = [
            'url' => $this->url,
            'database' => $this->database,
            'user_id' => $this->userId,
            'api_key_set' => !empty($this->apiKey),
            'curl_available' => function_exists('curl_init'),
            'connection_test' => false,
            'authentication_test' => false,
            'uid' => $this->userId,
            'error_message' => ''
        ];

        try {
            // Test the API call
            $apiTest = $this->testOdooApiCall();
            $status['connection_test'] = $apiTest['success'];
            $status['authentication_test'] = $apiTest['success'];
            
            if (!$apiTest['success']) {
                $status['error_message'] = $apiTest['error'];
            }

        } catch (Exception $e) {
            $status['error_message'] = $e->getMessage();
        }

        return $status;
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
                'base_url' => str_replace('/xmlrpc/2/object', '', $this->url),
                'database' => $this->database,
                'username' => 'admin',
                'password_set' => !empty($this->apiKey),
                'password_length' => strlen($this->apiKey),
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

        // Test 3: Test actual Odoo call using working format
        $diagnostics['step'] = 'Testing Odoo API call';
        $apiTest = $this->testOdooApiCall();
        $diagnostics['tests']['odoo_api_call'] = $apiTest['success'];
        if (!$apiTest['success']) {
            $diagnostics['errors'][] = 'Odoo API call failed: ' . $apiTest['error'];
        } else {
            $diagnostics['tests']['api_response'] = $apiTest['response'];
            $diagnostics['success'] = true;
        }

        // Test 4: Test quote search specifically
        if ($apiTest['success']) {
            $diagnostics['step'] = 'Testing quote search';
            $searchTest = $this->testQuoteSearch();
            $diagnostics['tests']['quote_search'] = $searchTest['success'];
            if (!$searchTest['success']) {
                $diagnostics['errors'][] = 'Quote search failed: ' . $searchTest['error'];
            } else {
                $diagnostics['tests']['quotes_found'] = count($searchTest['quotes']);
            }
        }

        $diagnostics['step'] = 'Diagnostics complete';
        return $diagnostics;
    }

    /**
     * Make Odoo call using the exact working XML-RPC format
     *
     * @param   string  $model   The Odoo model
     * @param   string  $method  The method to call
     * @param   array   $domain  Domain filters
     * @param   array   $fields  Fields to retrieve
     * @param   array   $options Additional options (limit, offset, order)
     *
     * @return  mixed   The result or false on error
     */
    private function odooCall($model, $method, $domain = [], $fields = [], $options = [])
    {
        try {
            $xmlRequest = '<?xml version="1.0"?>
<methodCall>
   <methodName>execute_kw</methodName>
   <params>
      <param>
         <value><string>' . htmlspecialchars($this->database) . '</string></value>
      </param>
      <param>
         <value><int>' . $this->userId . '</int></value>
      </param>
      <param>
         <value><string>' . htmlspecialchars($this->apiKey) . '</string></value>
      </param>
      <param>
         <value><string>' . htmlspecialchars($model) . '</string></value>
      </param>
      <param>
         <value><string>' . htmlspecialchars($method) . '</string></value>
      </param>
      <param>
         <value><array><data>';

            // Add domain as first argument
            if (!empty($domain)) {
                $xmlRequest .= '<value>' . $this->encodeDomain($domain) . '</value>';
            } else {
                $xmlRequest .= '<value><array><data/></array></value>';
            }

            $xmlRequest .= '</data></array></value>
      </param>';

            // Add keyword arguments if we have fields or options
            if (!empty($fields) || !empty($options)) {
                $xmlRequest .= '
      <param>
         <value>
            <struct>';

                // Add fields
                if (!empty($fields)) {
                    $xmlRequest .= '
               <member>
                  <name>fields</name>
                  <value>
                     <array>
                        <data>';
                    foreach ($fields as $field) {
                        $xmlRequest .= '<value><string>' . htmlspecialchars($field) . '</string></value>';
                    }
                    $xmlRequest .= '
                        </data>
                     </array>
                  </value>
               </member>';
                }

                // Add options (limit, offset, order)
                foreach ($options as $key => $value) {
                    $xmlRequest .= '
               <member>
                  <name>' . htmlspecialchars($key) . '</name>
                  <value>';
                    if (is_int($value)) {
                        $xmlRequest .= '<int>' . $value . '</int>';
                    } else {
                        $xmlRequest .= '<string>' . htmlspecialchars($value) . '</string>';
                    }
                    $xmlRequest .= '</value>
               </member>';
                }

                $xmlRequest .= '
            </struct>
         </value>
      </param>';
            }

            $xmlRequest .= '
   </params>
</methodCall>';

            $response = $this->makeCurlRequest($this->url, $xmlRequest);
            
            if ($response === false) {
                return false;
            }

            $result = $this->parseXmlResponse($response);
            
            if ($this->debug && $method === 'search_read') {
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
     * Make cURL request to Odoo using the exact working format
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
     * Encode domain filters for XML-RPC
     *
     * @param   array  $domain  Domain filters
     *
     * @return  string  Encoded XML
     */
    private function encodeDomain($domain)
    {
        $xml = '<array><data>';
        
        foreach ($domain as $condition) {
            if (is_array($condition) && count($condition) === 3) {
                $xml .= '<value><array><data>';
                $xml .= '<value><string>' . htmlspecialchars($condition[0]) . '</string></value>';
                $xml .= '<value><string>' . htmlspecialchars($condition[1]) . '</string></value>';
                if (is_int($condition[2])) {
                    $xml .= '<value><int>' . $condition[2] . '</int></value>';
                } else {
                    $xml .= '<value><string>' . htmlspecialchars($condition[2]) . '</string></value>';
                }
                $xml .= '</data></array></value>';
            }
        }
        
        $xml .= '</data></array>';
        return $xml;
    }

    /**
     * Test basic connectivity to Odoo server
     */
    private function testBasicConnectivity()
    {
        $baseUrl = str_replace('/xmlrpc/2/object', '', $this->url);
        
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $baseUrl,
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
     * Test Odoo API call using the exact working format
     */
    private function testOdooApiCall()
    {
        // Test with a simple search_count call on res.partner
        $xmlRequest = '<?xml version="1.0"?>
<methodCall>
   <methodName>execute_kw</methodName>
   <params>
      <param>
         <value><string>' . htmlspecialchars($this->database) . '</string></value>
      </param>
      <param>
         <value><int>' . $this->userId . '</int></value>
      </param>
      <param>
         <value><string>' . htmlspecialchars($this->apiKey) . '</string></value>
      </param>
      <param>
         <value><string>res.partner</string></value>
      </param>
      <param>
         <value><string>search_count</string></value>
      </param>
      <param>
         <value><array><data/></array></value>
      </param>
   </params>
</methodCall>';

        $response = $this->makeCurlRequest($this->url, $xmlRequest);
        
        if ($response === false) {
            return ['success' => false, 'error' => 'No response from Odoo API'];
        }

        $result = $this->parseXmlResponse($response);
        
        if ($result === false) {
            return ['success' => false, 'error' => 'Invalid API response', 'raw_response' => substr($response, 0, 500)];
        }

        return ['success' => true, 'response' => $result];
    }

    /**
     * Test quote search
     */
    private function testQuoteSearch()
    {
        // Test search for sale.order records
        $xmlRequest = '<?xml version="1.0"?>
<methodCall>
   <methodName>execute_kw</methodName>
   <params>
      <param>
         <value><string>' . htmlspecialchars($this->database) . '</string></value>
      </param>
      <param>
         <value><int>' . $this->userId . '</int></value>
      </param>
      <param>
         <value><string>' . htmlspecialchars($this->apiKey) . '</string></value>
      </param>
      <param>
         <value><string>sale.order</string></value>
      </param>
      <param>
         <value><string>search_count</string></value>
      </param>
      <param>
         <value><array><data/></array></value>
      </param>
   </params>
</methodCall>';

        $response = $this->makeCurlRequest($this->url, $xmlRequest);
        
        if ($response === false) {
            return ['success' => false, 'error' => 'No response from quote search'];
        }

        $result = $this->parseXmlResponse($response);
        
        if ($result === false) {
            return ['success' => false, 'error' => 'Invalid search response'];
        }

        return ['success' => true, 'quotes' => is_numeric($result) ? (int)$result : 0];
    }

    /**
     * Safely get partner ID from quote data
     *
     * @param   array  $quote  Quote data
     *
     * @return  integer  Partner ID
     */
    private function getPartnerId($quote)
    {
        if (!isset($quote['partner_id'])) {
            return 0;
        }

        $partnerId = $quote['partner_id'];
        
        // If it's an array [id, name], return the ID
        if (is_array($partnerId) && count($partnerId) >= 1) {
            return (int) $partnerId[0];
        }
        
        // If it's a direct integer
        if (is_numeric($partnerId)) {
            return (int) $partnerId;
        }
        
        return 0;
    }

    /**
     * Safely get contact name from quote data
     *
     * @param   array  $quote  Quote data
     *
     * @return  string  Contact name
     */
    private function getContactName($quote)
    {
        if (!isset($quote['partner_id'])) {
            return 'Cliente no disponible';
        }

        $partnerId = $quote['partner_id'];
        
        // If it's an array [id, name], return the name
        if (is_array($partnerId) && count($partnerId) >= 2) {
            return (string) $partnerId[1];
        }
        
        // If it's just an ID, we don't have the name
        return 'Cliente ID: ' . $partnerId;
    }

    /**
     * Check if a quote number is valid
     *
     * @param   string  $quoteName  The quote name/number to validate
     *
     * @return  boolean  True if valid, false otherwise
     */
    private function isValidQuoteNumber($quoteName)
    {
        // Convert to string and trim
        $quoteName = trim((string) $quoteName);
        
        // Check if empty
        if (empty($quoteName)) {
            return false;
        }
        
        // Convert to lowercase for case-insensitive comparison
        $lowerName = strtolower($quoteName);
        
        // List of invalid values
        $invalidValues = [
            'sin número',
            'sin numero',
            'new',
            'draft',
            'borrador',
            'false',
            'none',
            'null',
            'undefined'
        ];
        
        // Check against invalid values
        if (in_array($lowerName, $invalidValues)) {
            return false;
        }
        
        // Must be at least 3 characters
        if (strlen($quoteName) < 3) {
            return false;
        }
        
        // Must contain at least one letter or number
        if (!preg_match('/[a-zA-Z0-9]/', $quoteName)) {
            return false;
        }
        
        // Valid quote number
        return true;
    }

    /**
     * Extract numeric part from quote number for sorting
     *
     * @param   string  $quoteName  The quote name/number
     *
     * @return  integer  The numeric part for sorting
     */
    private function extractQuoteNumber($quoteName)
    {
        // Convert to string and trim
        $quoteName = trim((string) $quoteName);
        
        // For quotes like S00010, S00005, etc., extract the numeric part
        if (preg_match('/^[A-Za-z]*(\d+)/', $quoteName, $matches)) {
            $number = (int) $matches[1]; // Return the numeric part
            return $number;
        }
        
        // Fallback: extract all numbers and use the largest
        preg_match_all('/\d+/', $quoteName, $matches);
        if (!empty($matches[0])) {
            $numbers = array_map('intval', $matches[0]);
            return max($numbers);
        }
        
        // If no numbers found, return 0 for sorting
        return 0;
    }
}