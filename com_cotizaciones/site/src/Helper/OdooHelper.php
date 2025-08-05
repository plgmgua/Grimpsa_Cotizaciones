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
use Joomla\CMS\Log\Log;

/**
 * Helper class for Odoo Quotes API operations
 */
class OdooHelper
{
    /**
     * Odoo configuration
     */
    private $config;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->config = ComponentHelper::getParams('com_cotizaciones');
    }

    /**
     * Get fields information for sale.order model to discover custom fields
     *
     * @return  array  Array of field information
     */
    public function getSaleOrderFields()
    {
        $xmlPayload = '<?xml version="1.0"?>
<methodCall>
   <methodName>execute_kw</methodName>
   <params>
      <param>
         <value><string>grupoimpre</string></value>
      </param>
      <param>
         <value><int>2</int></value>
      </param>
      <param>
         <value><string>2386bb5ae66c7fd9022feaf82148680c4cf4ce3b</string></value>
      </param>
      <param>
         <value><string>sale.order</string></value>
      </param>
      <param>
         <value><string>fields_get</string></value>
      </param>
      <param>
         <value><array><data/></array></value>
      </param>
   </params>
</methodCall>';

        $result = $this->executeOdooCall($xmlPayload);
        
        if (!$result) {
            return [];
        }

        // Parse the fields response
        if (isset($result['params']['param']['value']['struct']['member'])) {
            $fields = [];
            foreach ($result['params']['param']['value']['struct']['member'] as $member) {
                $fieldName = $member['name'];
                $fields[$fieldName] = $member['value'];
            }
            return $fields;
        }

        return [];
    }

    /**
     * Find the custom field name for sales agent in sale.order model
     *
     * @return  string|null  The custom field name or null if not found
     */
    public function findSalesAgentFieldInQuotes()
    {
        $fields = $this->getSaleOrderFields();
        
        // Look for fields that might be the sales agent field
        $possibleFields = [
            'x_studio_agente_de_ventas_1',
            'x_studio_vendedor',
            'x_studio_agente_de_ventas',
            'x_studio_agente_ventas',
            'x_studio_sales_agent',
            'x_agente_de_ventas',
            'x_sales_agent',
            'x_vendedor'
        ];
        
        foreach ($possibleFields as $fieldName) {
            if (isset($fields[$fieldName])) {
                if ($this->config->get('enable_debug', 0)) {
                    Log::add('Found sales agent field in quotes: ' . $fieldName, Log::DEBUG, 'com_cotizaciones');
                }
                return $fieldName;
            }
        }
        
        // If no custom field found, log all available custom fields for debugging
        if ($this->config->get('enable_debug', 0)) {
            $customFields = [];
            foreach ($fields as $fieldName => $fieldInfo) {
                if (strpos($fieldName, 'x_') === 0) {
                    $customFields[] = $fieldName;
                }
            }
            Log::add('Available custom fields in sale.order: ' . implode(', ', $customFields), Log::DEBUG, 'com_cotizaciones');
        }
        
        return null;
    }

    /**
     * Execute Odoo XML-RPC call
     *
     * @param   string  $xmlPayload  The XML payload
     *
     * @return  mixed  The response data or false on failure
     */
    private function executeOdooCall($xmlPayload)
    {
        // Log the request if debug is enabled
        if ($this->config->get('enable_debug', 0)) {
            Log::add('Odoo Quotes API Request: ' . substr($xmlPayload, 0, 1000) . '...', Log::DEBUG, 'com_cotizaciones');
        }
        
        $curl = curl_init();
        
        curl_setopt_array($curl, [
            CURLOPT_URL => 'https://grupoimpre.odoo.com/xmlrpc/2/object',
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_ENCODING => '',
            CURLOPT_MAXREDIRS => 10,
            CURLOPT_TIMEOUT => 0,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_HTTP_VERSION => CURL_HTTP_VERSION_1_1,
            CURLOPT_CUSTOMREQUEST => 'POST',
            CURLOPT_POSTFIELDS => $xmlPayload,
            CURLOPT_HTTPHEADER => [
                'Content-Type: text/xml',
                'X-Openerp-Session-Id: 2386bb5ae66c7fd9022feaf82148680c4cf4ce3b'
            ],
        ]);

        $response = curl_exec($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
        $error = curl_error($curl);
        curl_close($curl);

        if ($this->config->get('enable_debug', 0)) {
            Log::add('Odoo Quotes API Call - HTTP Code: ' . $httpCode, Log::DEBUG, 'com_cotizaciones');
            Log::add('Odoo Quotes API Response: ' . substr($response, 0, 2000) . '...', Log::DEBUG, 'com_cotizaciones');
            if ($error) {
                Log::add('Odoo Quotes API Error: ' . $error, Log::ERROR, 'com_cotizaciones');
            }
        }

        if ($httpCode !== 200 || !$response) {
            Log::add('Odoo Quotes API Failed - HTTP: ' . $httpCode . ', Error: ' . $error, Log::ERROR, 'com_cotizaciones');
            return false;
        }

        // Load XML string
        $xml = simplexml_load_string($response);
        if (!$xml) {
            Log::add('Failed to parse Odoo XML response', Log::ERROR, 'com_cotizaciones');
            return false;
        }

        // Convert XML to JSON
        $json = json_encode($xml);
        return json_decode($json, true);
    }

    /**
     * Get quotes by sales agent
     *
     * @param   string   $agentName  The sales agent name
     * @param   integer  $page       The page number
     * @param   integer  $limit      The number of quotes per page
     * @param   string   $search     The search term
     *
     * @return  array  Array of quotes
     */
    public function getQuotesByAgent($agentName, $page = 1, $limit = 20, $search = '')
    {
        // First, try to find the custom sales agent field in quotes
        $salesAgentField = $this->findSalesAgentFieldInQuotes();
        
        if ($salesAgentField) {
            // Use the custom field if found
            return $this->getQuotesByCustomField($agentName, $salesAgentField, $page, $limit, $search);
        }
        
        // Fallback to the old method using Odoo user mapping
        // Get quotes from sale.order model filtered by sales agent
        $xmlPayload = '<?xml version="1.0"?>
<methodCall>
   <methodName>execute_kw</methodName>
   <params>
      <param>
         <value><string>grupoimpre</string></value>
      </param>
      <param>
         <value><int>2</int></value>
      </param>
      <param>
         <value><string>2386bb5ae66c7fd9022feaf82148680c4cf4ce3b</string></value>
      </param>
      <param>
         <value><string>sale.order</string></value>
      </param>
      <param>
         <value><string>search_read</string></value>
      </param>
      <param>
         <value>
            <array>
               <data>
                  <value>
                     <array>
                        <data>
                           <value>
                              <array>
                                 <data>
                                    <value><string>x_studio_agente_de_ventas_1</string></value>
                                    <value><string>=</string></value>
                                    <value><string>' . htmlspecialchars($agentName, ENT_XML1, 'UTF-8') . '</string></value>
                                 </data>
                              </array>
                           </value>
                        </data>
                     </array>
                  </value>
               </data>
            </array>
         </value>
      </param>
      <param>
         <value>
            <struct>
               <member>
                  <name>fields</name>
                  <value>
                     <array>
                        <data>
                           <value><string>name</string></value>
                           <value><string>partner_id</string></value>
                           <value><string>date_order</string></value>
                           <value><string>amount_total</string></value>
                           <value><string>state</string></value>
                           <value><string>user_id</string></value>
                           <value><string>x_studio_agente_de_ventas_1</string></value>
                           <value><string>note</string></value>
                           <value><string>order_line</string></value>
                        </data>
                     </array>
                  </value>
               </member>
            </struct>
         </value>
      </param>
   </params>
</methodCall>';

        $result = $this->executeOdooCall($xmlPayload);
        
        if (!$result) {
            return [];
        }

        return $this->parseQuotesWithContactNames($result);
    }

    /**
     * Get quotes by custom sales agent field
     *
     * @param   string   $agentName        The sales agent name
     * @param   string   $salesAgentField  The custom field name
     * @param   integer  $page             The page number
     * @param   integer  $limit            The number of quotes per page
     * @param   string   $search           The search term
     *
     * @return  array  Array of quotes
     */
    private function getQuotesByCustomField($agentName, $salesAgentField, $page = 1, $limit = 20, $search = '')
    {
        $xmlPayload = '<?xml version="1.0"?>
<methodCall>
   <methodName>execute_kw</methodName>
   <params>
      <param>
         <value><string>grupoimpre</string></value>
      </param>
      <param>
         <value><int>2</int></value>
      </param>
      <param>
         <value><string>2386bb5ae66c7fd9022feaf82148680c4cf4ce3b</string></value>
      </param>
      <param>
         <value><string>sale.order</string></value>
      </param>
      <param>
         <value><string>search_read</string></value>
      </param>
      <param>
         <value>
            <array>
               <data>
                  <value>
                     <array>
                        <data>
                           <value>
                              <array>
                                 <data>
                                    <value><string>' . $salesAgentField . '</string></value>
                                    <value><string>=</string></value>
                                    <value><string>' . htmlspecialchars($agentName, ENT_XML1, 'UTF-8') . '</string></value>
                                 </data>
                              </array>
                           </value>
                        </data>
                     </array>
                  </value>
               </data>
            </array>
         </value>
      </param>
      <param>
         <value>
            <struct>
               <member>
                  <name>fields</name>
                  <value>
                     <array>
                        <data>
                           <value><string>name</string></value>
                           <value><string>partner_id</string></value>
                           <value><string>date_order</string></value>
                           <value><string>amount_total</string></value>
                           <value><string>state</string></value>
                           <value><string>user_id</string></value>
                           <value><string>' . $salesAgentField . '</string></value>
                           <value><string>note</string></value>
                        </data>
                     </array>
                  </value>
               </member>
            </struct>
         </value>
      </param>
   </params>
</methodCall>';

        $result = $this->executeOdooCall($xmlPayload);
        
        if (!$result) {
            return [];
        }

        return $this->parseQuotesResponse($result);
    }

    /**
     * Get quotes by contact ID
     *
     * @param   integer  $contactId  The contact ID
     *
     * @return  array  Array of quotes for this contact
     */
    public function getQuotesByContact($contactId)
    {
        // First, try to find the custom sales agent field
        $salesAgentField = $this->findSalesAgentFieldInQuotes();
        
        if ($salesAgentField) {
            // Use the custom field if found
            return $this->getQuotesByContactWithCustomField($contactId, $salesAgentField);
        }
        
        // Fallback to the old method
        $xmlPayload = '<?xml version="1.0"?>
<methodCall>
   <methodName>execute_kw</methodName>
   <params>
      <param>
         <value><string>grupoimpre</string></value>
      </param>
      <param>
         <value><int>2</int></value>
      </param>
      <param>
         <value><string>2386bb5ae66c7fd9022feaf82148680c4cf4ce3b</string></value>
      </param>
      <param>
         <value><string>sale.order</string></value>
      </param>
      <param>
         <value><string>search_read</string></value>
      </param>
      <param>
         <value>
            <array>
               <data>
                  <value>
                     <array>
                        <data>
                           <value>
                              <array>
                                 <data>
                                    <value><string>partner_id</string></value>
                                    <value><string>=</string></value>
                                    <value><int>' . $contactId . '</int></value>
                                 </data>
                              </array>
                           </value>
                        </data>
                     </array>
                  </value>
               </data>
            </array>
         </value>
      </param>
      <param>
         <value>
            <struct>
               <member>
                  <name>fields</name>
                  <value>
                     <array>
                        <data>
                           <value><string>name</string></value>
                           <value><string>partner_id</string></value>
                           <value><string>date_order</string></value>
                           <value><string>amount_total</string></value>
                           <value><string>state</string></value>
                           <value><string>user_id</string></value>
                           <value><string>x_studio_agente_de_ventas_1</string></value>
                           <value><string>note</string></value>
                        </data>
                     </array>
                  </value>
               </member>
            </struct>
         </value>
      </param>
   </params>
</methodCall>';

        $result = $this->executeOdooCall($xmlPayload);
        
        if (!$result) {
            return [];
        }

        return $this->parseQuotesResponse($result);
    }

    /**
     * Get quotes by contact ID using custom sales agent field
     *
     * @param   integer  $contactId        The contact ID
     * @param   string   $salesAgentField  The custom field name
     *
     * @return  array  Array of quotes for this contact
     */
    private function getQuotesByContactWithCustomField($contactId, $salesAgentField)
    {
        $user = Factory::getUser();
        $agentName = $user->name;
        
        $xmlPayload = '<?xml version="1.0"?>
<methodCall>
   <methodName>execute_kw</methodName>
   <params>
      <param>
         <value><string>grupoimpre</string></value>
      </param>
      <param>
         <value><int>2</int></value>
      </param>
      <param>
         <value><string>2386bb5ae66c7fd9022feaf82148680c4cf4ce3b</string></value>
      </param>
      <param>
         <value><string>sale.order</string></value>
      </param>
      <param>
         <value><string>search_read</string></value>
      </param>
      <param>
         <value>
            <array>
               <data>
                  <value>
                     <array>
                        <data>
                           <value>
                              <array>
                                 <data>
                                    <value><string>partner_id</string></value>
                                    <value><string>=</string></value>
                                    <value><int>' . $contactId . '</int></value>
                                 </data>
                              </array>
                           </value>
                           <value>
                              <array>
                                 <data>
                                    <value><string>' . $salesAgentField . '</string></value>
                                    <value><string>=</string></value>
                                    <value><string>' . htmlspecialchars($agentName, ENT_XML1, 'UTF-8') . '</string></value>
                                 </data>
                              </array>
                           </value>
                        </data>
                     </array>
                  </value>
               </data>
            </array>
         </value>
      </param>
      <param>
         <value>
            <struct>
               <member>
                  <name>fields</name>
                  <value>
                     <array>
                        <data>
                           <value><string>name</string></value>
                           <value><string>partner_id</string></value>
                           <value><string>date_order</string></value>
                           <value><string>amount_total</string></value>
                           <value><string>state</string></value>
                           <value><string>user_id</string></value>
                           <value><string>' . $salesAgentField . '</string></value>
                           <value><string>note</string></value>
                        </data>
                     </array>
                  </value>
               </member>
            </struct>
         </value>
      </param>
   </params>
</methodCall>';

        $result = $this->executeOdooCall($xmlPayload);
        
        if (!$result) {
            return [];
        }

        return $this->parseQuotesResponse($result);
    }

    /**
     * Get single quote by ID
     *
     * @param   integer  $quoteId  The quote ID
     *
     * @return  array|null  Quote data or null if not found
     */
    public function getQuote($quoteId)
    {
        $xmlPayload = '<?xml version="1.0"?>
<methodCall>
   <methodName>execute_kw</methodName>
   <params>
      <param>
         <value><string>grupoimpre</string></value>
      </param>
      <param>
         <value><int>2</int></value>
      </param>
      <param>
         <value><string>2386bb5ae66c7fd9022feaf82148680c4cf4ce3b</string></value>
      </param>
      <param>
         <value><string>sale.order</string></value>
      </param>
      <param>
         <value><string>search_read</string></value>
      </param>
      <param>
         <value>
            <array>
               <data>
                  <value>
                     <array>
                        <data>
                           <value>
                              <array>
                                 <data>
                                    <value><string>id</string></value>
                                    <value><string>=</string></value>
                                    <value><int>' . $quoteId . '</int></value>
                                 </data>
                              </array>
                           </value>
                        </data>
                     </array>
                  </value>
               </data>
            </array>
         </value>
      </param>
      <param>
         <value>
            <struct>
               <member>
                  <name>fields</name>
                  <value>
                     <array>
                        <data>
                           <value><string>name</string></value>
                           <value><string>partner_id</string></value>
                           <value><string>date_order</string></value>
                           <value><string>amount_total</string></value>
                           <value><string>state</string></value>
                           <value><string>user_id</string></value>
                           <value><string>x_studio_agente_de_ventas_1</string></value>
                           <value><string>note</string></value>
                           <value><string>order_line</string></value>
                        </data>
                     </array>
                  </value>
               </member>
            </struct>
         </value>
      </param>
   </params>
</methodCall>';

        $result = $this->executeOdooCall($xmlPayload);
        
        if (!$result) {
            return null;
        }

        $quotes = $this->parseQuotesResponse($result);
        return !empty($quotes) ? $quotes[0] : null;
    }

    /**
     * Get contact information by ID (public method for model access)
     *
     * @param   integer  $contactId  The contact ID
     *
     * @return  array|null  Contact data or null if not found
     */
    public function getContactInfo($contactId)
    {
        return $this->getContactInfo($contactId);
    }

    /**
     * Create new quote
     *
     * @param   array  $quoteData  The quote data
     *
     * @return  mixed  The quote ID on success, false on failure
     */
    public function createQuote($quoteData)
    {
        // Ensure we always set the sales agent field
        $user = Factory::getUser();
        $agentName = $user->name;
        
        // Always set the primary field
        $quoteData['x_studio_agente_de_ventas_1'] = $agentName;
        
        // Extract quote lines if provided
        $quoteLines = [];
        if (isset($quoteData['quote_lines']) && is_array($quoteData['quote_lines'])) {
            $quoteLines = $quoteData['quote_lines'];
            unset($quoteData['quote_lines']); // Remove from main data
        }
        
        $xmlPayload = '<?xml version="1.0"?>
        <methodCall>
            <methodName>execute_kw</methodName>
            <params>
                <param><value><string>grupoimpre</string></value></param>
                <param><value><int>2</int></value></param>
                <param><value><string>2386bb5ae66c7fd9022feaf82148680c4cf4ce3b</string></value></param>
                <param><value><string>sale.order</string></value></param>
                <param><value><string>create</string></value></param>
                <param>
                    <value>
                        <array>
                            <data>
                                <value>
                                    <struct>
                                        <member>
                                            <name>partner_id</name>
                                            <value><int>' . (int)($quoteData['partner_id'] ?? 0) . '</int></value>
                                        </member>
                                        <member>
                                            <name>date_order</name>
                                            <value><string>' . ($quoteData['date_order'] ?? date('Y-m-d H:i:s')) . '</string></value>
                                        </member>
                                        <member>
                                            <name>note</name>
                                            <value><string>' . htmlspecialchars($quoteData['note'] ?? '', ENT_XML1, 'UTF-8') . '</string></value>
                                        </member>
                                        <member>
                                            <name>x_studio_agente_de_ventas_1</name>
                                            <value><string>' . htmlspecialchars($agentName, ENT_XML1, 'UTF-8') . '</string></value>
                                        </member>
                                    </struct>
                                </value>
                            </data>
                        </array>
                    </value>
                </param>
            </params>
        </methodCall>';

        $result = $this->executeOdooCall($xmlPayload);
        $quoteId = $this->parseCreateResponse($result);
        
        // If quote was created successfully and we have lines, add them
        if ($quoteId && !empty($quoteLines)) {
            foreach ($quoteLines as $line) {
                $this->addQuoteLineToExistingQuote($quoteId, $line);
            }
        }
        
        return $quoteId;
    }
    
    /**
     * Add a quote line to an existing quote
     *
     * @param   integer  $quoteId  The quote ID
     * @param   array    $lineData The line data
     *
     * @return  mixed  The line ID on success, false on failure
     */
    private function addQuoteLineToExistingQuote($quoteId, $lineData)
    {
        // First create the product
        $productId = $this->createProduct($lineData['description'], $lineData['product_id']);
        
        if (!$productId) {
            return false;
        }
        
        // Then create the quote line
        $xmlPayload = '<?xml version="1.0"?>
        <methodCall>
            <methodName>execute_kw</methodName>
            <params>
                <param><value><string>grupoimpre</string></value></param>
                <param><value><int>2</int></value></param>
                <param><value><string>2386bb5ae66c7fd9022feaf82148680c4cf4ce3b</string></value></param>
                <param><value><string>sale.order.line</string></value></param>
                <param><value><string>create</string></value></param>
                <param>
                    <value>
                        <array>
                            <data>
                                <value>
                                    <struct>
                                        <member>
                                            <name>order_id</name>
                                            <value><int>' . $quoteId . '</int></value>
                                        </member>
                                        <member>
                                            <name>product_id</name>
                                            <value><int>' . $productId . '</int></value>
                                        </member>
                                        <member>
                                            <name>name</name>
                                            <value><string>' . htmlspecialchars($lineData['description'], ENT_XML1, 'UTF-8') . '</string></value>
                                        </member>
                                        <member>
                                            <name>product_uom_qty</name>
                                            <value><double>' . (float)($lineData['quantity'] ?? 1) . '</double></value>
                                        </member>
                                        <member>
                                            <name>price_unit</name>
                                            <value><double>' . (float)($lineData['price'] ?? 0) . '</double></value>
                                        </member>
                                    </struct>
                                </value>
                            </data>
                        </array>
                    </value>
                </param>
            </params>
        </methodCall>';

        $result = $this->executeOdooCall($xmlPayload);
        return $this->parseCreateResponse($result);
    }
    
    /**
     * Create a product with correlative number
     *
     * @param   string   $description  Product description
     * @param   integer  $productNumber Product number
     *
     * @return  mixed  The product ID on success, false on failure
     */
    public function createProduct($description, $productNumber = null)
    {
        if ($productNumber === null) {
            $productNumber = $this->getNextProductNumber();
        }
        
        $xmlPayload = '<?xml version="1.0"?>
        <methodCall>
            <methodName>execute_kw</methodName>
            <params>
                <param><value><string>grupoimpre</string></value></param>
                <param><value><int>2</int></value></param>
                <param><value><string>2386bb5ae66c7fd9022feaf82148680c4cf4ce3b</string></value></param>
                <param><value><string>product.product</string></value></param>
                <param><value><string>create</string></value></param>
                <param>
                    <value>
                        <array>
                            <data>
                                <value>
                                    <struct>
                                        <member>
                                            <name>name</name>
                                            <value><string>' . htmlspecialchars($productNumber, ENT_XML1, 'UTF-8') . '</string></value>
                                        </member>
                                        <member>
                                            <name>description</name>
                                            <value><string>' . htmlspecialchars($description, ENT_XML1, 'UTF-8') . '</string></value>
                                        </member>
                                        <member>
                                            <name>type</name>
                                            <value><string>service</string></value>
                                        </member>
                                        <member>
                                            <name>sale_ok</name>
                                            <value><boolean>1</boolean></value>
                                        </member>
                                        <member>
                                            <name>purchase_ok</name>
                                            <value><boolean>0</boolean></value>
                                        </member>
                                    </struct>
                                </value>
                            </data>
                        </array>
                    </value>
                </param>
            </params>
        </methodCall>';

        $result = $this->executeOdooCall($xmlPayload);
        return $this->parseCreateResponse($result);
    }
    
    /**
     * Get the next available product number
     *
     * @return  integer  The next product number
     */
    private function getNextProductNumber()
    {
        // For now, return a timestamp-based number
        // In a real implementation, you might query existing products
        return time();
    }

    /**
     * Update existing quote
     *
     * @param   integer  $quoteId    The quote ID
     * @param   array    $quoteData  The quote data
     *
     * @return  boolean  True on success, false on failure
     */
    public function updateQuote($quoteId, $quoteData)
    {
        // Ensure we always maintain the sales agent field
        $user = Factory::getUser();
        $agentName = $user->name;
        
        // Always maintain the primary field
        $quoteData['x_studio_agente_de_ventas_1'] = $agentName;
        
        $xmlPayload = '<?xml version="1.0"?>
        <methodCall>
            <methodName>execute_kw</methodName>
            <params>
                <param><value><string>grupoimpre</string></value></param>
                <param><value><int>2</int></value></param>
                <param><value><string>2386bb5ae66c7fd9022feaf82148680c4cf4ce3b</string></value></param>
                <param><value><string>sale.order</string></value></param>
                <param><value><string>write</string></value></param>
                <param>
                    <value>
                        <array>
                            <data>
                                <value>
                                    <array>
                                        <data>
                                            <value><int>' . $quoteId . '</int></value>
                                        </data>
                                    </array>
                                </value>
                                <value>
                                    <struct>
                                        ' . $this->buildQuoteXmlFields($quoteData) . '
                                    </struct>
                                </value>
                            </data>
                        </array>
                    </value>
                </param>
            </params>
        </methodCall>';

        $result = $this->executeOdooCall($xmlPayload);
        return $result !== false;
    }

    /**
     * Delete quote
     *
     * @param   integer  $quoteId  The quote ID
     *
     * @return  boolean  True on success, false on failure
     */
    public function deleteQuote($quoteId)
    {
        $xmlPayload = '<?xml version="1.0"?>
        <methodCall>
            <methodName>execute_kw</methodName>
            <params>
                <param><value><string>grupoimpre</string></value></param>
                <param><value><int>2</int></value></param>
                <param><value><string>2386bb5ae66c7fd9022feaf82148680c4cf4ce3b</string></value></param>
                <param><value><string>sale.order</string></value></param>
                <param><value><string>unlink</string></value></param>
                <param>
                    <value>
                        <array>
                            <data>
                                <value>
                                    <array>
                                        <data>
                                            <value><int>' . $quoteId . '</int></value>
                                        </data>
                                    </array>
                                </value>
                            </data>
                        </array>
                    </value>
                </param>
            </params>
        </methodCall>';

        $result = $this->executeOdooCall($xmlPayload);
        return $result !== false;
    }

    /**
     * Delete quote line
     *
     * @param   integer  $lineId  Line ID
     *
     * @return  boolean  True on success, false on failure
     */
    public function deleteQuoteLine($lineId)
    {
        $xmlPayload = '<?xml version="1.0"?>
        <methodCall>
            <methodName>execute_kw</methodName>
            <params>
                <param><value><string>grupoimpre</string></value></param>
                <param><value><int>2</int></value></param>
                <param><value><string>2386bb5ae66c7fd9022feaf82148680c4cf4ce3b</string></value></param>
                <param><value><string>sale.order.line</string></value></param>
                <param><value><string>unlink</string></value></param>
                <param>
                    <value>
                        <array>
                            <data>
                                <value>
                                    <array>
                                        <data>
                                            <value><int>' . $lineId . '</int></value>
                                        </data>
                                    </array>
                                </value>
                            </data>
                        </array>
                    </value>
                </param>
            </params>
        </methodCall>';

        $result = $this->executeOdooCall($xmlPayload);
        return $result !== false;
    }

    /**
     * Parse quotes response from Odoo
     *
     * @param   mixed  $result  The API response
     *
     * @return  array  Array of quotes
     */
    private function parseQuotesWithContactNames($result)
    {
        if (!$result || !isset($result['params']['param']['value']['array']['data']['value'])) {
            return [];
        }

        $quotes = [];
        $values = $result['params']['param']['value']['array']['data']['value'];

        // Handle single quote response
        if (isset($values['struct'])) {
            $values = [$values];
        }

        foreach ($values as $value) {
            if (!isset($value['struct']['member'])) {
                continue;
            }

            $quote = [];
            foreach ($value['struct']['member'] as $member) {
                $fieldName = $member['name'];
                $fieldValue = '';
                
                if (isset($member['value']['string'])) {
                    $fieldValue = $member['value']['string'];
                } elseif (isset($member['value']['int'])) {
                    $fieldValue = (string)$member['value']['int'];
                } elseif (isset($member['value']['double'])) {
                    $fieldValue = (string)$member['value']['double'];
                } elseif (isset($member['value']['array']['data']['value'])) {
                    // Handle partner_id array format [id, name]
                    $arrayData = $member['value']['array']['data']['value'];
                    if (is_array($arrayData) && count($arrayData) >= 2) {
                        if ($fieldName === 'partner_id') {
                            $quote['partner_id'] = isset($arrayData[0]['int']) ? (string)$arrayData[0]['int'] : '';
                            $quote['contact_name'] = isset($arrayData[1]['string']) ? $arrayData[1]['string'] : '';
                            continue;
                        }
                    }
                }
                
                $quote[$fieldName] = $fieldValue;
            }
            
            $quotes[] = $this->normalizeQuote($quote);
        }

        return $quotes;
    }

    /**
     * Parse quotes response from Odoo (fallback method)
     *
     * @param   mixed  $result  The API response
     *
     * @return  array  Array of quotes
     */
    private function parseQuotesResponse($result)
    {
        return $this->parseQuotesWithContactNames($result);
    }

    /**
     * Normalize quote data
     *
     * @param   array  $quote  Raw quote data
     *
     * @return  array  Normalized quote data
     */
    private function normalizeQuote($quote)
    {
        return [
            'id' => isset($quote['id']) ? $quote['id'] : '0',
            'name' => isset($quote['name']) ? $quote['name'] : '',
            'partner_id' => isset($quote['partner_id']) ? $quote['partner_id'] : '0',
            'contact_name' => isset($quote['contact_name']) ? $quote['contact_name'] : '',
            'date_order' => isset($quote['date_order']) ? $quote['date_order'] : '',
            'amount_total' => isset($quote['amount_total']) ? $quote['amount_total'] : '0.00',
            'state' => isset($quote['state']) ? $quote['state'] : 'draft',
            'note' => isset($quote['note']) ? $quote['note'] : ''
        ];
    }

    /**
     * Build XML fields for quote data
     *
     * @param   array  $quoteData  The quote data
     *
     * @return  string  The XML fields
     */
    private function buildQuoteXmlFields($quoteData)
    {
        $fields = '';
        $fieldMap = [
            'partner_id' => 'partner_id',
            'date_order' => 'date_order',
            'note' => 'note',
            'x_studio_agente_de_ventas_1' => 'x_studio_agente_de_ventas_1',
        ];

        foreach ($fieldMap as $xmlField => $dataField) {
            if (isset($quoteData[$dataField]) && $quoteData[$dataField] !== '') {
                if ($xmlField === 'partner_id') {
                    $fields .= '<member>
                        <name>' . $xmlField . '</name>
                        <value><int>' . (int)$quoteData[$dataField] . '</int></value>
                    </member>';
                } else {
                    $value = htmlspecialchars($quoteData[$dataField], ENT_XML1, 'UTF-8');
                    $fields .= '<member>
                        <name>' . $xmlField . '</name>
                        <value><string>' . $value . '</string></value>
                    </member>';
                }
            }
        }

        return $fields;
    }

    /**
     * Parse create response from Odoo
     *
     * @param   mixed  $result  The API response
     *
     * @return  mixed  The quote ID on success, false on failure
     */
    private function parseCreateResponse($result)
    {
        if (!$result || !isset($result['params']['param']['value']['int'])) {
            return false;
        }

        return $result['params']['param']['value']['int'];
    }
}