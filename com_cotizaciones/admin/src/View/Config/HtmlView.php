<?php
/**
 * @package     Grimpsa.Administrator
 * @subpackage  com_cotizaciones
 *
 * @copyright   Copyright (C) 2025 Grimpsa. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

namespace Grimpsa\Component\Cotizaciones\Administrator\View\Config;

defined('_JEXEC') or die;

use Joomla\CMS\Language\Text;
use Joomla\CMS\MVC\View\HtmlView as BaseHtmlView;
use Joomla\CMS\Toolbar\ToolbarHelper;

/**
 * View class for the Cotizaciones configuration.
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
     * Display the view
     *
     * @param   string  $tpl  The name of the template file to parse
     *
     * @return  void
     */
    public function display($tpl = null)
    {
        $this->form = $this->get('Form');
        $this->item = $this->get('Item');

        // Check for errors.
        if (count($errors = $this->get('Errors'))) {
            throw new \Exception(implode("\n", $errors), 500);
        }

        $this->addToolbar();

        parent::display($tpl);
    }

    /**
     * Add the page title and toolbar.
     *
     * @return  void
     */
    protected function addToolbar()
    {
        ToolbarHelper::title(Text::_('COM_COTIZACIONES_CONFIG_TITLE'), 'cog');
        
        ToolbarHelper::apply('config.save');
        ToolbarHelper::save('config.save');
        ToolbarHelper::custom('config.testConnection', 'broadcast', 'broadcast', Text::_('COM_COTIZACIONES_CONFIG_TEST_CONNECTION'), false);
        ToolbarHelper::cancel('config.cancel');
    }
}