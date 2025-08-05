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
use Joomla\CMS\MVC\Model\AdminModel;
use Grimpsa\Component\Cotizaciones\Site\Helper\OdooHelper;

/**
 * Cotizacion model for the Cotizaciones component.
 */
class CotizacionModel extends AdminModel
{
    /**
     * The type alias for this content type.
     *
     * @var    string
     */
    public $typeAlias = 'com_cotizaciones.cotizacion';

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
            'com_cotizaciones.cotizacion', 
            'cotizacion', 
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
     * Method to get a single record.
     *
     * @param   integer  $pk  The id of the primary key.
     *
     * @return  mixed  Object on success, false on failure.
     */
    public function getItem($pk = null)
    {
        $app = Factory::getApplication();
        $input = $app->input;
        
        // Get the ID from input or state, default to 0 for new quotes
        if ($pk === null) {
            $pk = (int) $this->getState($this->getName() . '.id', 0);
        }
        
        $pk = (int) $pk;

        // Always return a valid object structure
        $defaultItem = (object) [
            'id' => $pk,
            'name' => '',
            'partner_id' => 0,
            'date_order' => date('Y-m-d'),
            'amount_total' => '0.00',
            'state' => 'draft',
            'note' => ''
        ];
        
        // For new quotes, check if we have pre-filled data from URL parameters
        if ($pk <= 0) {
            $contactId = $input->getInt('contact_id', 0);
            if ($contactId > 0) {
                $defaultItem->partner_id = $contactId;
                
                // Try to get contact name for better UX
                try {
                    if (class_exists('\Grimpsa\Component\OdooContacts\Site\Helper\OdooHelper')) {
                        $contactsHelper = new \Grimpsa\Component\OdooContacts\Site\Helper\OdooHelper();
                        $contact = $contactsHelper->getContact($contactId);
                        if ($contact && isset($contact['name'])) {
                            $defaultItem->contact_name = $contact['name'];
                        }
                    }
                } catch (Exception $e) {
                    // Silently handle error
                }
            }
            
            return $defaultItem;
        }

        try {
            $helper = new OdooHelper();
            $quote = $helper->getQuote($pk);

            if (!$quote) {
                return $defaultItem;
            }

            // Get contact information if partner_id exists
            if (!empty($quote['partner_id']) && (int)$quote['partner_id'] > 0) {
                $contact = $helper->getContactInfo((int)$quote['partner_id']);
                if ($contact) {
                    $quote['contact_name'] = $contact['name'] ?? '';
                    $quote['contact_vat'] = $contact['vat'] ?? '';
                    $quote['contact_email'] = $contact['email'] ?? '';
                }
            }
            // Ensure all properties exist
            foreach ($defaultItem as $key => $value) {
                if (!isset($quote[$key])) {
                    $quote[$key] = $value;
                }
            }

            return (object) $quote;
        } catch (Exception $e) {
            Factory::getApplication()->enqueueMessage('Error loading quote: ' . $e->getMessage(), 'warning');
            return $defaultItem;
        }
    }

    /**
     * Method to create a new quote in Odoo.
     *
     * @param   array  $data  The quote data.
     *
     * @return  mixed  The quote ID on success, false on failure.
     */
    public function createQuote($data)
    {
        $helper = new OdooHelper();
        return $helper->createQuote($data);
    }

    /**
     * Method to update a quote in Odoo.
     *
     * @param   integer  $quoteId  The quote ID.
     * @param   array    $data     The quote data.
     *
     * @return  boolean  True on success, false on failure.
     */
    public function updateQuote($quoteId, $data)
    {
        $helper = new OdooHelper();
        return $helper->updateQuote($quoteId, $data);
    }

    /**
     * Method to delete a quote from Odoo.
     *
     * @param   integer  $quoteId  The quote ID.
     *
     * @return  boolean  True on success, false on failure.
     */
    public function deleteQuote($quoteId)
    {
        $helper = new OdooHelper();
        return $helper->deleteQuote($quoteId);
    }

    /**
     * Method to get the data that should be injected in the form.
     *
     * @return  mixed  The data for the form.
     */
    protected function loadFormData()
    {
        // Check the session for previously entered form data.
        $app = Factory::getApplication();
        $data = $app->getUserState('com_cotizaciones.edit.cotizacion.data', []);

        if (empty($data)) {
            $data = $this->getItem();
        }

        return $data;
    }

    /**
     * Method to auto-populate the model state.
     *
     * @return  void
     */
    protected function populateState()
    {
        $app = Factory::getApplication();

        // Load the User state.
        $pk = $app->input->getInt('id');
        $this->setState($this->getName() . '.id', $pk);

        // Load the parameters.
        $params = $app->getParams();
        $this->setState('params', $params);
    }
}