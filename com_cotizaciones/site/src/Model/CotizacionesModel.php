<?php
/**
 * @package     Grimpsa.Site
 * @subpackage  com_cotizaciones
 *
 * @copyright   Copyright (C) 2025 Grimpsa. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

namespace Grimpsa\Component\Cotizaciones\Site\Model;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Model\ListModel;
use Joomla\CMS\Pagination\Pagination;
use Grimpsa\Component\Cotizaciones\Site\Helper\OdooHelper;

/**
 * Cotizaciones model for the Cotizaciones component.
 */
class CotizacionesModel extends ListModel
{
    /**
     * Constructor.
     *
     * @param   array  $config  An optional associative array of configuration settings.
     */
    public function __construct($config = [])
    {
        if (empty($config['filter_fields'])) {
            $config['filter_fields'] = [
                'id', 'name', 'partner_id', 'date_order', 'amount_total', 'state'
            ];
        }

        parent::__construct($config);
    }

    /**
     * Method to get a list of quotes.
     *
     * @return  array  An array of quotes.
     */
    public function getItems()
    {
        $user = Factory::getUser();
        
        if ($user->guest) {
            return [];
        }

        try {
            $helper = new OdooHelper();
            // Get pagination and search parameters
            $limitstart = $this->getStart();
            $limit = $this->getState('list.limit', 20);
            $search = $this->getState('filter.search', '');
            
            $page = floor($limitstart / $limit) + 1;
            
            $quotes = $helper->getQuotesByAgent($user->name, $page, $limit, $search);
            
            // Ensure we return a proper array
            if (!is_array($quotes)) {
                return [];
            }
            
            // Sort quotes by date_order (newest first) - this is the default order
            usort($quotes, function($a, $b) {
                $dateA = isset($a['date_order']) ? strtotime($a['date_order']) : 0;
                $dateB = isset($b['date_order']) ? strtotime($b['date_order']) : 0;
                return $dateB - $dateA; // Descending order (newest first)
            });
            
            // Validate and normalize each quote
            $validQuotes = [];
            foreach ($quotes as $quote) {
                if (is_array($quote)) {
                    // Ensure all expected fields exist as strings
                    $normalizedQuote = [
                        'id' => isset($quote['id']) ? (string)$quote['id'] : '0',
                        'name' => isset($quote['name']) && is_string($quote['name']) ? $quote['name'] : '',
                        'partner_id' => isset($quote['partner_id']) ? $quote['partner_id'] : '0',
                        'contact_name' => isset($quote['contact_name']) && is_string($quote['contact_name']) ? $quote['contact_name'] : '',
                        'date_order' => isset($quote['date_order']) && is_string($quote['date_order']) ? $quote['date_order'] : '',
                        'amount_total' => isset($quote['amount_total']) ? (string)$quote['amount_total'] : '0.00',
                        'state' => isset($quote['state']) && is_string($quote['state']) ? $quote['state'] : 'draft',
                        'note' => isset($quote['note']) && is_string($quote['note']) ? $quote['note'] : ''
                    ];
                    
                    $validQuotes[] = $normalizedQuote;
                }
            }
            
            return $validQuotes;
            
        } catch (Exception $e) {
            Factory::getApplication()->enqueueMessage('Error connecting to Odoo: ' . $e->getMessage(), 'error');
            return [];
        }
    }

    /**
     * Method to get quotes by contact ID.
     *
     * @param   integer  $contactId  The contact ID
     *
     * @return  array  An array of quotes for this contact.
     */
    public function getQuotesByContact($contactId)
    {
        try {
            $helper = new OdooHelper();
            $quotes = $helper->getQuotesByContact($contactId);
            
            if (!is_array($quotes)) {
                return [];
            }
            
            return $quotes;
            
        } catch (Exception $e) {
            Factory::getApplication()->enqueueMessage('Error loading quotes: ' . $e->getMessage(), 'error');
            return [];
        }
    }

    /**
     * Method to get the total number of quotes.
     *
     * @return  integer  The total number of quotes.
     */
    public function getTotal()
    {
        $user = Factory::getUser();
        
        if ($user->guest) {
            return 0;
        }

        try {
            $helper = new OdooHelper();
            $quotes = $helper->getQuotesByAgent($user->name, 1, 1000); // Get a large number to count
            return is_array($quotes) ? count($quotes) : 0;
        } catch (Exception $e) {
            return 0;
        }
    }

    /**
     * Method to get a pagination object for the quotes.
     *
     * @return  Pagination  A Pagination object for the quotes.
     */
    public function getPagination()
    {
        // Get the pagination request variables
        $limit = $this->getState('list.limit', 20);
        $limitstart = $this->getState('list.start', 0);

        // Get the total number of quotes
        $total = $this->getTotal();

        // Create the pagination object
        return new Pagination($total, $limitstart, $limit);
    }

    /**
     * Method to auto-populate the model state.
     *
     * @param   string  $ordering   An optional ordering field.
     * @param   string  $direction  An optional direction (asc|desc).
     *
     * @return  void
     */
    protected function populateState($ordering = 'date_order', $direction = 'desc')
    {
        $app = Factory::getApplication();
        
        // Get the pagination request variables
        $limit = $app->getUserStateFromRequest('global.list.limit', 'limit', $app->get('list_limit'), 'uint');
        $this->setState('list.limit', $limit);

        $limitstart = $app->input->get('limitstart', 0, 'uint');
        $this->setState('list.start', $limitstart);

        // Get the search filter
        $search = $app->input->get('filter_search', '', 'string');
        $search = trim($search);
        $this->setState('filter.search', $search);

        // Set the ordering (newest first by default)
        $this->setState('list.ordering', $ordering);
        $this->setState('list.direction', $direction);
    }
}