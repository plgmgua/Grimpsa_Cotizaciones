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
        
        try {
            $this->form = $this->get('Form');
            $this->item = $this->get('Item');
            $this->state = $this->get('State');
            
            // Ensure item is always an object with required properties
            if (!is_object($this->item)) {
                $this->item = (object) [
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
        } catch (Exception $e) {
            // If there's an error, create a default empty item
            $this->item = (object) [
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

        // Check for errors.
        if (count($errors = $this->get('Errors'))) {
            // Don't throw exception, just log errors and continue
            foreach ($errors as $error) {
                Factory::getApplication()->enqueueMessage($error, 'warning');
            }
        }

        $this->_prepareDocument();

        parent::display($tpl);
    }

    /**
     * Prepares the document
     *
     * @return  void
     */
    protected function _prepareDocument()
    {
        $app = Factory::getApplication();
        $isNew = ($this->item->id == 0);
        
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