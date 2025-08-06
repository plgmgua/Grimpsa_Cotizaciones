<?php
/**
 * @package     Grimpsa.Site
 * @subpackage  com_cotizaciones
 *
 * @copyright   Copyright (C) 2025 Grimpsa. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

namespace Grimpsa\Component\Cotizaciones\Site\View\Cotizacion;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;

/**
 * HTML Cotizacion View class for the Cotizaciones component
 */
class HtmlView extends BaseHtmlView
{
    /**
     * The Form object
     *
     * @var    Form
     */
    protected $form;

    /**
     * The active item
     *
     * @var    object
     */
    protected $item;

    /**
     * The model state
     *
     * @var    Registry
     */
    protected $state;

    /**
     * The application input object
     *
     * @var    Input
     */
    protected $input;

    /**
     * Display the view
     *
     * @param   string  $tpl  The name of the template file to parse
     *
     * @return  void
     */
    public function display($tpl = null)
    {
        $app = Factory::getApplication();
        $this->input = $app->input;
        
        // Always initialize with a safe default item structure
        $this->item = $this->createDefaultItem();
        
        try {
            // Get the item from model
            $modelItem = $this->get('Item');
            
            // Only use model item if it's valid
            if (is_object($modelItem) && isset($modelItem->id)) {
                $this->item = $modelItem;
            }
            
            // Ensure all required properties exist
            $this->item = $this->ensureItemProperties($this->item);
            
        } catch (Exception $e) {
            // Log error but continue with default item
            $app->enqueueMessage('Error loading quote data: ' . $e->getMessage(), 'warning');
        }

        // Try to get form (optional)
        try {
            $this->form = $this->get('Form');
        } catch (Exception $e) {
            $this->form = null;
        }

        // Try to get state (optional)
        try {
            $this->state = $this->get('State');
        } catch (Exception $e) {
            $this->state = new \Joomla\Registry\Registry();
        }

        $this->_prepareDocument();

        parent::display($tpl);
    }

    /**
     * Create a default item with all required properties
     *
     * @return  object  Default item object
     */
    private function createDefaultItem()
    {
        return (object) [
            'id' => 0,
            'name' => '',
            'partner_id' => 0,
            'contact_name' => '',
            'date_order' => date('Y-m-d'),
            'amount_total' => '0.00',
            'state' => 'draft',
            'note' => ''
        ];
    }

    /**
     * Ensure item has all required properties
     *
     * @param   mixed  $item  The item to check
     *
     * @return  object  Item with all properties
     */
    private function ensureItemProperties($item)
    {
        $defaultItem = $this->createDefaultItem();
        
        // If item is not an object, return default
        if (!is_object($item)) {
            return $defaultItem;
        }
        
        // Ensure all properties exist
        foreach ($defaultItem as $key => $defaultValue) {
            if (!isset($item->$key)) {
                $item->$key = $defaultValue;
            }
        }
        
        return $item;
    }

    /**
     * Prepares the document
     *
     * @return  void
     */
    protected function _prepareDocument()
    {
        $app = Factory::getApplication();
        $isNew = (isset($this->item->id) && $this->item->id == 0);
        
        $title = $isNew ? Text::_('COM_COTIZACIONES_COTIZACION_NEW') : Text::_('COM_COTIZACIONES_COTIZACION_EDIT');
        
        if ($app->get('sitename_pagetitles', 0) == 1) {
            $title = Text::sprintf('JPAGETITLE', $app->get('sitename'), $title);
        } elseif ($app->get('sitename_pagetitles', 0) == 2) {
            $title = Text::sprintf('JPAGETITLE', $title, $app->get('sitename'));
        }

        $this->document->setTitle($title);
        
        // Add CSS and JS
        HTMLHelper::_('bootstrap.framework');
        HTMLHelper::_('behavior.formvalidator');
        HTMLHelper::_('stylesheet', 'com_cotizaciones/cotizaciones.css', ['version' => 'auto', 'relative' => true]);
    }
}