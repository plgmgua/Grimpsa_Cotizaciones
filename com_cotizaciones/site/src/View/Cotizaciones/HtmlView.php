<?php
/**
 * @package     Grimpsa.Site
 * @subpackage  com_cotizaciones
 *
 * @copyright   Copyright (C) 2025 Grimpsa. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

namespace Grimpsa\Component\Cotizaciones\Site\View\Cotizaciones;

defined('_JEXEC') or die;

use Joomla\CMS\Factory;
use Joomla\CMS\HTML\HTMLHelper;
use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Component\ComponentHelper;

/**
 * HTML Cotizaciones View class for the Cotizaciones component
 */
class HtmlView extends BaseHtmlView
{
    /**
     * The search tools form
     *
     * @var    Form
     */
    public $filterForm;

    /**
     * The active search filters
     *
     * @var    array
     */
    public $activeFilters = [];

    /**
     * An array of items
     *
     * @var    array
     */
    protected $items = [];

    /**
     * The pagination object
     *
     * @var    Pagination
     */
    protected $pagination;

    /**
     * The model state
     *
     * @var    Registry
     */
    protected $state;

    /**
     * The component parameters
     *
     * @var    Registry
     */
    protected $params;

    /**
     * Display the view
     *
     * @param   string  $tpl  The name of the template file to parse
     *
     * @return  void
     */
    public function display($tpl = null)
    {
        try {
            $this->items = $this->get('Items');
            $this->pagination = $this->get('Pagination');
            $this->state = $this->get('State');
            $this->distinctClients = $this->get('DistinctClients');
            $this->params = ComponentHelper::getParams('com_cotizaciones');
        } catch (Exception $e) {
            // If there's an error getting items, set empty defaults
            $this->items = [];
            $this->pagination = null;
            $this->state = new \Joomla\Registry\Registry();
            $this->distinctClients = [];
            $this->params = ComponentHelper::getParams('com_cotizaciones');
            
            // Log the error for debugging
            Factory::getApplication()->enqueueMessage($e->getMessage(), 'error');
        }

        // Check for errors.
        if (count($errors = $this->get('Errors'))) {
            Factory::getApplication()->enqueueMessage(implode("\n", $errors), 'error');
            $this->items = [];
            $this->pagination = null;
            $this->state = new \Joomla\Registry\Registry();
            $this->distinctClients = [];
        }

        $this->addToolbar();
        $this->_prepareDocument();

        parent::display($tpl);
    }

    /**
     * Add the page title and toolbar.
     *
     * @return  void
     */
    protected function addToolbar()
    {
        $user = Factory::getUser();
        
        // Set the title
        $this->document->setTitle(Text::_('COM_COTIZACIONES_COTIZACIONES_TITLE'));
        
        // Add CSS and JS
        HTMLHelper::_('bootstrap.framework');
        HTMLHelper::_('script', 'com_cotizaciones/cotizaciones.js', ['version' => 'auto', 'relative' => true]);
        HTMLHelper::_('stylesheet', 'com_cotizaciones/cotizaciones.css', ['version' => 'auto', 'relative' => true]);
    }

    /**
     * Prepares the document
     *
     * @return  void
     */
    protected function _prepareDocument()
    {
        $app = Factory::getApplication();
        $menus = $app->getMenu();
        $menu = $menus->getActive();

        // Initialize params if not set
        if (!$this->params) {
            $this->params = ComponentHelper::getParams('com_cotizaciones');
        }

        if ($menu) {
            $this->params->def('page_heading', $this->params->get('page_title', $menu->title));
        } else {
            $this->params->def('page_heading', Text::_('COM_COTIZACIONES_COTIZACIONES_TITLE'));
        }

        $title = $this->params->get('page_title', '');

        if (empty($title)) {
            $title = $app->get('sitename');
        } elseif ($app->get('sitename_pagetitles', 0) == 1) {
            $title = Text::sprintf('JPAGETITLE', $app->get('sitename'), $title);
        } elseif ($app->get('sitename_pagetitles', 0) == 2) {
            $title = Text::sprintf('JPAGETITLE', $title, $app->get('sitename'));
        }

        $this->document->setTitle($title);

        if ($this->params->get('menu-meta_description')) {
            $this->document->setDescription($this->params->get('menu-meta_description'));
        }

        if ($this->params->get('menu-meta_keywords')) {
            $this->document->setMetadata('keywords', $this->params->get('menu-meta_keywords'));
        }

        if ($this->params->get('robots')) {
            $this->document->setMetadata('robots', $this->params->get('robots'));
        }
    }
}