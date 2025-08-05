<?php
/**
 * @package     Grimpsa.Site
 * @subpackage  com_cotizaciones
 *
 * @copyright   Copyright (C) 2025 Grimpsa. All rights reserved.
 * @license     GNU General Public License version 2 or later
 */

namespace Grimpsa\Component\Cotizaciones\Site\Service;

defined('_JEXEC') or die;

use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Component\Router\RouterView;
use Joomla\CMS\Component\Router\RouterViewConfiguration;
use Joomla\CMS\Component\Router\Rules\MenuRules;
use Joomla\CMS\Component\Router\Rules\NomenuRules;
use Joomla\CMS\Component\Router\Rules\StandardRules;
use Joomla\CMS\Menu\AbstractMenu;

/**
 * Routing class from com_cotizaciones
 */
class Router extends RouterView
{
    /**
     * The constructor for this router
     *
     * @param   SiteApplication  $app   The application object
     * @param   AbstractMenu     $menu  The menu object to work with
     */
    public function __construct(SiteApplication $app, AbstractMenu $menu)
    {
        $cotizaciones = new RouterViewConfiguration('cotizaciones');
        $this->registerView($cotizaciones);

        $cotizacion = new RouterViewConfiguration('cotizacion');
        $cotizacion->setKey('id');
        $this->registerView($cotizacion);

        parent::__construct($app, $menu);

        $this->attachRule(new MenuRules($this));
        $this->attachRule(new StandardRules($this));
        $this->attachRule(new NomenuRules($this));
    }

    /**
     * Method to get the segment(s) for a cotizacion
     *
     * @param   string  $id     ID of the cotizacion to retrieve the segments for
     * @param   array   $query  The request that is built right now
     *
     * @return  array|string  The segments of this item
     */
    public function getCotizacionSegment($id, $query)
    {
        return [(int) $id => $id];
    }

    /**
     * Method to get the segment(s) for cotizaciones
     *
     * @param   string  $id     ID of the cotizaciones to retrieve the segments for
     * @param   array   $query  The request that is built right now
     *
     * @return  array|string  The segments of this item
     */
    public function getCotizacionesSegment($id, $query)
    {
        return [];
    }

    /**
     * Method to get the id for a cotizacion
     *
     * @param   string  $segment  Segment to retrieve the ID for
     * @param   array   $query    The request that is parsed right now
     *
     * @return  mixed   The id of this item or false
     */
    public function getCotizacionId($segment, $query)
    {
        return (int) $segment;
    }

    /**
     * Method to get the id for cotizaciones
     *
     * @param   string  $segment  Segment to retrieve the ID for
     * @param   array   $query    The request that is parsed right now
     *
     * @return  mixed   The id of this item or false
     */
    public function getCotizacionesId($segment, $query)
    {
        return 1;
    }
}