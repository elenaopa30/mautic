<?php

/*
 * @copyright   2016 Mautic Contributors. All rights reserved
 * @author      Mautic, Inc.
 *
 * @link        https://mautic.org
 *
 * @license     GNU/GPLv3 http://www.gnu.org/licenses/gpl-3.0.html
 */

namespace Mautic\CoreBundle\Event;

use Mautic\CoreBundle\Templating\Helper\ButtonHelper;
use Symfony\Component\EventDispatcher\Event;
use Symfony\Component\HttpFoundation\Request;

class CustomButtonEvent extends Event
{
    /**
     * Button location requested.
     *
     * @var
     */
    protected $location;

    /**
     * @var Request
     */
    protected $request;

    /**
     * @var
     */
    protected $route;

    /**
     * @var array
     */
    protected $routeParams = [];

    /**
     * @var array
     */
    protected $buttons = [];

    /**
     * Entity for list/view actions.
     *
     * @var mixed
     */
    protected $item;

    /**
     * CustomButtonEvent constructor.
     *
     * @param         $location
     * @param Request $request
     * @param array   $buttons
     * @param null    $item
     */
    public function __construct($location, Request $request, array $buttons = [], $item = null)
    {
        $this->location = $location;
        $this->item     = $item;

        $this->request = ($request->isXmlHttpRequest() && $request->query->has('request')) ? $request->query->get('request') : $request;
        if ($this->request->attributes->has('ajaxRoute')) {
            $ajaxRoute         = $this->request->attributes->get('ajaxRoute');
            $this->route       = $ajaxRoute['_route'];
            $this->routeParams = $ajaxRoute['_route_params'];
        } else {
            $this->route       = $this->request->attributes->get('_route');
            $this->routeParams = $this->request->attributes->get('_route_params');
        }

        if (null === $this->routeParams) {
            $this->routeParams = [];
        }

        foreach ($buttons as $button) {
            $this->buttons[$this->generateButtonKey($button)] = $button;
        }
    }

    /**
     * @return mixed
     */
    public function getLocation()
    {
        return $this->location;
    }

    /**
     * @return request
     */
    public function getRequest()
    {
        return $this->request;
    }

    /**
     * Get Symfony route name for the current view.
     *
     * @param bool $withParams
     *
     * @return array|mixed
     */
    public function getRoute($withParams = false)
    {
        return ($withParams) ? [$this->route, $this->routeParams] : $this->route;
    }

    /**
     * @return array
     */
    public function getButtons()
    {
        return $this->buttons;
    }

    /**
     * Add an array of buttons.
     *
     * @param array $buttons
     * @param null  $location
     * @param null  $route
     *
     * @return $this
     */
    public function addButtons(array $buttons, $location = null, $route = null)
    {
        if (!$this->checkLocationContext($location) || !$this->checkRouteContext($route)) {
            return $this;
        }

        foreach ($buttons as $key => $button) {
            if (!isset($button['priority'])) {
                $button['priority'] = 0;
            }

            $this->buttons[$this->generateButtonKey($button)] = $button;
        }

        return $this;
    }

    /**
     * Add a single button.
     *
     * @param array $button
     * @param null  $location
     * @param null  $route
     *
     * @return $this
     */
    public function addButton(array $button, $location = null, $route = null)
    {
        if (!$this->checkLocationContext($location) || !$this->checkRouteContext($route)) {
            return $this;
        }

        if (!isset($button['priority'])) {
            $button['priority'] = 0;
        }

        $this->buttons[$this->generateButtonKey($button)] = $button;

        return $this;
    }

    /**
     * @param $button
     */
    public function removeButton($button)
    {
        $buttonKey = $this->generateButtonKey($button);
        if (isset($this->buttons[$buttonKey])) {
            unset($this->buttons[$buttonKey]);
        }
    }

    /**
     * @return mixed
     */
    public function getItem()
    {
        return $this->item;
    }

    /**
     * @param $location
     *
     * @return bool
     */
    public function checkLocationContext($location)
    {
        if (null !== $location) {
            if ((is_array($location) && !in_array($this->location, $location)) || (is_string($location) && $location !== $this->location)) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param $route
     *
     * @return bool
     */
    public function checkRouteContext($route)
    {
        if (null !== $route) {
            list($currentRoute, $routeParams) = $this->getRoute(true);
            $givenRoute                       = $route;
            $givenRouteParams                 = [];
            if (is_array($route)) {
                list($givenRoute, $givenRouteParams) = $route;
            }

            if ($givenRoute !== $currentRoute) {
                return false;
            }

            foreach ($givenRouteParams as $param => $value) {
                if (!isset($routeParams[$param]) || $value !== $routeParams[$param]) {
                    return false;
                }
            }
        }

        return true;
    }

    /**
     * Generate a button ID that can be overridden by other plugins.
     *
     * @param $button
     *
     * @return string
     */
    protected function generateButtonKey($button)
    {
        $buttonKey = '';
        if (!empty($button['btnText'])) {
            $buttonKey .= $button['btnText'];
        } elseif (isset($button['confirm'])) {
            if (!empty($button['confirm']['btnText'])) {
                $buttonKey .= $button['confirm']['btnText'];
            }

            if (!empty($button['confirm']['template'])) {
                $buttonKey .= $button['confirm']['template'];
            }

            if (!empty($button['confirm']['iconClass'])) {
                $buttonKey .= $button['confirm']['iconClass'];
            }
        }

        if (!empty($button['iconClass'])) {
            $buttonKey .= $button['iconClass'];
        }

        // Ensure buttons aren't overwritten unintentionally
        if (empty($buttonKey)) {
            $buttonKey = uniqid(time());
        }

        if (ButtonHelper::LOCATION_NAVBAR !== $this->location) {
            // Include the request
            list($currentRoute, $routeParams) = $this->getRoute(true);

            $buttonKey .= $currentRoute;

            foreach ($routeParams as $paramKey => $paramValue) {
                $buttonKey .= $paramKey.$paramValue;
            }
        }

        return $buttonKey;
    }
}
