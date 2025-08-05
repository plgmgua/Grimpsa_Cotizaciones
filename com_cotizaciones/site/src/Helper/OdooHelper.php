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
            // For now, return mock data to ensure the component works
            $mockQuotes = [
                [
                    'id' => 1,
                    'name' => 'SO001',
                    'partner_id' => 123,
                    'contact_name' => 'Cliente Ejemplo 1',
                    'date_order' => date('Y-m-d'),
                    'amount_total' => '1500.00',
                    'state' => 'draft',
                    'note' => 'Cotización de ejemplo'
                ],
                [
                    'id' => 2,
                    'name' => 'SO002',
                    'partner_id' => 124,
                    'contact_name' => 'Cliente Ejemplo 2',
                    'date_order' => date('Y-m-d', strtotime('-1 day')),
                    'amount_total' => '2500.00',
                    'state' => 'sent',
                    'note' => 'Segunda cotización'
                ]
            ];

            // Filter by search if provided
            if (!empty($search)) {
                $mockQuotes = array_filter($mockQuotes, function($quote) use ($search) {
                    return stripos($quote['contact_name'], $search) !== false;
                });
            }

            return array_values($mockQuotes);
            
        } catch (Exception $e) {
            if ($this->debug) {
                Factory::getApplication()->enqueueMessage('Odoo Error: ' . $e->getMessage(), 'error');
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
            // Return mock data for now
            return [
                'id' => $quoteId,
                'name' => 'SO' . str_pad($quoteId, 3, '0', STR_PAD_LEFT),
                'partner_id' => 123,
                'contact_name' => 'Cliente Ejemplo',
                'date_order' => date('Y-m-d'),
                'amount_total' => '1500.00',
                'state' => 'draft',
                'note' => 'Cotización de ejemplo'
            ];
            
        } catch (Exception $e) {
            if ($this->debug) {
                Factory::getApplication()->enqueueMessage('Odoo Error: ' . $e->getMessage(), 'error');
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
            // For now, return a mock ID
            return rand(100, 999);
            
        } catch (Exception $e) {
            if ($this->debug) {
                Factory::getApplication()->enqueueMessage('Odoo Error: ' . $e->getMessage(), 'error');
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
            // For now, always return true
            return true;
            
        } catch (Exception $e) {
            if ($this->debug) {
                Factory::getApplication()->enqueueMessage('Odoo Error: ' . $e->getMessage(), 'error');
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
            // For now, always return true
            return true;
            
        } catch (Exception $e) {
            if ($this->debug) {
                Factory::getApplication()->enqueueMessage('Odoo Error: ' . $e->getMessage(), 'error');
            }
            return false;
        }
    }

    /**
     * Search for companies/clients
     *
     * @param   string  $search  Search term
     * @param   integer $limit   Number of results
     *
     * @return  array   Array of clients
     */
    public function searchClients($search, $limit = 10)
    {
        try {
            // Return mock client data
            $mockClients = [
                [
                    'id' => 123,
                    'name' => 'Grupo Impre S.A.',
                    'vat' => '12345678-9',
                    'email' => 'info@grupoimpre.com'
                ],
                [
                    'id' => 124,
                    'name' => 'Sofia Grant',
                    'vat' => '98765432-1',
                    'email' => 'sofia@example.com'
                ]
            ];

            // Filter by search term
            if (!empty($search)) {
                $mockClients = array_filter($mockClients, function($client) use ($search) {
                    return stripos($client['name'], $search) !== false;
                });
            }

            return array_slice(array_values($mockClients), 0, $limit);
            
        } catch (Exception $e) {
            if ($this->debug) {
                Factory::getApplication()->enqueueMessage('Odoo Error: ' . $e->getMessage(), 'error');
            }
            return [];
        }
    }

    /**
     * Get contact information
     *
     * @param   integer  $contactId  Contact ID
     *
     * @return  array|false  Contact data or false
     */
    public function getContactInfo($contactId)
    {
        try {
            // Return mock contact data
            return [
                'id' => $contactId,
                'name' => 'Cliente Ejemplo',
                'vat' => '12345678-9',
                'email' => 'cliente@example.com'
            ];
            
        } catch (Exception $e) {
            if ($this->debug) {
                Factory::getApplication()->enqueueMessage('Odoo Error: ' . $e->getMessage(), 'error');
            }
            return false;
        }
    }
}