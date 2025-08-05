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
 * Helper class for Odoo integration
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
     * Make XML-RPC call to Odoo
     *
     * @param   string  $model   The Odoo model
     * @param   string  $method  The method to call
     * @param   array   $args    Arguments for the method
     *
     * @return  mixed   The result or false on error
     */
    private function xmlrpcCall($model, $method, $args = [])
    {
        try {
            // Prepare the XML-RPC request
            $request = xmlrpc_encode_request('execute_kw', [
                $this->database,
                $this->userId,
                $this->apiKey,
                $model,
                $method,
                $args
            ]);

            // Create context for the HTTP request
            $context = stream_context_create([
                'http' => [
                    'method' => 'POST',
                    'header' => 'Content-Type: text/xml',
                    'content' => $request,
                    'timeout' => 30
                ]
            ]);

            // Make the request
            $response = file_get_contents($this->url, false, $context);
            
            if ($response === false) {
                if ($this->debug) {
                    Factory::getApplication()->enqueueMessage('Failed to connect to Odoo server', 'error');
                }
                return false;
            }

            // Decode the response
            $result = xmlrpc_decode($response);
            
            if (is_array($result) && xmlrpc_is_fault($result)) {
                if ($this->debug) {
                    Factory::getApplication()->enqueueMessage('Odoo Error: ' . $result['faultString'], 'error');
                }
                return false;
            }

            return $result;

        } catch (Exception $e) {
            if ($this->debug) {
                Factory::getApplication()->enqueueMessage('XML-RPC Error: ' . $e->getMessage(), 'error');
            }
            return false;
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
            $quoteIds = $this->xmlrpcCall('sale.order', 'search', [
                $domain,
                ['offset' => $offset, 'limit' => $limit, 'order' => 'date_order desc']
            ]);

            if (!$quoteIds || !is_array($quoteIds)) {
                return [];
            }

            // Get quote details
            $quotes = $this->xmlrpcCall('sale.order', 'read', [
                $quoteIds,
                ['id', 'name', 'partner_id', 'date_order', 'amount_total', 'state', 'note']
            ]);

            if (!$quotes || !is_array($quotes)) {
                return [];
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
            $quotes = $this->xmlrpcCall('sale.order', 'read', [
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

            $quoteId = $this->xmlrpcCall('sale.order', 'create', [$quoteData]);

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

            $result = $this->xmlrpcCall('sale.order', 'write', [[$quoteId], $updateData]);

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
            $result = $this->xmlrpcCall('sale.order', 'unlink', [[$quoteId]]);
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
        try {
            $result = $this->xmlrpcCall('res.partner', 'search', [[], ['limit' => 1]]);
            return is_array($result) && !empty($result);
        } catch (Exception $e) {
            return false;
        }
    }
}