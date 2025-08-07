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
        
        // Get the ID from input or state, default to 0 for new quotes
        if ($pk === null) {
            $pk = (int) $app->input->getInt('id', 0);
        }
        
        $pk = (int) $pk;

        // Create default item structure
        $defaultItem = [
            'id' => $pk,
            'name' => '',
            'partner_id' => 0,
            'contact_name' => '',
            'date_order' => date('Y-m-d'),
            'amount_total' => '0.00',
            'state' => 'draft',
            'note' => ''
        ];
        
        // For new quotes, return default item
        if ($pk <= 0) {
            return (object) $defaultItem;
        }

        try {
            $helper = new OdooHelper();
            $quote = $helper->getQuote($pk);

            if (!$quote || !is_array($quote) || empty($quote)) {
                return (object) $defaultItem;
            }

            // Ensure all properties exist
            foreach ($defaultItem as $key => $defaultValue) {
                if (!isset($quote[$key]) || $quote[$key] === null) {
                    $quote[$key] = $defaultValue;
                }
            }

            return (object) $quote;
        } catch (Exception $e) {
            Factory::getApplication()->enqueueMessage('Error loading quote: ' . $e->getMessage(), 'warning');
            return (object) $defaultItem;
        }
    }

    /**
     * Method to get quote lines for a quote.
     *
     * @param   integer  $quoteId  The quote ID.
     *
     * @return  array    Array of quote lines.
     */
    public function getQuoteLines($quoteId)
    {
        if ($quoteId <= 0) {
            return [];
        }

        try {
            $helper = new OdooHelper();
            return $helper->getQuoteLines($quoteId);
        } catch (Exception $e) {
            Factory::getApplication()->enqueueMessage('Error loading quote lines: ' . $e->getMessage(), 'warning');
            return [];
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
        
        // Ensure app is available
        if (!$app) {
            return;
        }

        // Load the User state.
        $pk = $app->input->getInt('id');
        $this->setState($this->getName() . '.id', $pk);

        // Load the parameters.
        $params = $app->getParams();
        $this->setState('params', $params);
    }
}