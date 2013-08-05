<?php
/**
 * Created by JetBrains PhpStorm.
 * User: lunereaper
 * Date: 07.05.2013
 * Time: 15:07
 * To change this template use File | Settings | File Templates.
 */

namespace alchemy\future\app;


use alchemy\app\Callback;
use alchemy\future\app\router\Route;

class Router
{
    /**
     * Creates new route
     * @see Route
     * @see Callback
     *
     * @param $pattern
     * @param $resource
     *
     * eg.
     * addRoute('some/${controller}/${action}?', '${controller}Controller->${action:defaultAction};
     */
    public function addRoute($pattern, $callback)
    {
        if (!isset($this->routes[$callback])) {
            $this->routes[$callback] = array();
        }
        $this->routes[$callback][] = new Route($pattern);
    }

    public function addTranslator($callable)
    {

    }

    /**
     * Gets route from set URI
     * @param string $uri
     * @return Callback
     */
    public function getCallback($uri)
    {
        foreach ($this->routes as $callback => $routes) {
            foreach ($routes as $route) {

                if ($route->isMatch($uri)) {
                    $callback = new Callback($callback);
                    $data = $route->getData();
                    $callback->bindParameters($data);
                    $callback->setArguments(array($data));
                    return $callback;
                }
            }
        }
    }

    /**
     * Gets URI for callable
     */
    public function getURI($callable, $data = array())
    {
        
    }

    protected $routes = array();
    protected $translators = array();
}