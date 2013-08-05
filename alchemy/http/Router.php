<?php
/**
 * Alchemy Framework (http://alchemyframework.org/)
 *
 * @link      http://github.com/dkraczkowski/alchemy for the canonical source repository
 * @copyright Copyright (c) 2012-2013 Dawid Kraczkowski
 * @license   https://raw.github.com/dkraczkowski/alchemy/master/LICENSE New BSD License
 */
namespace alchemy\http;
use alchemy\http\Request;
use alchemy\app\Callback;
use alchemy\http\router\Route;

class RouterException extends \Exception {}
class RouterInvalidRequestMethodException extends RouterException {}
/**
 *
 */
class Router
{
    /**
     * Gets current route
     *
     * @return \alchemy\http\router\Route
     */
    public function getRoute()
    {
        $this->processRouteSearch();
        return $this->currentRoute;
    }

    /**
     * Forces router to reprocess matching route everytime when
     * getResource or getRoute is called
     */
    public function setForceMode()
    {
        $this->forceMode = true;
    }

    /**
     * Gets current resource
     *
     * @return \alchemy\app\Callback
     */
    public function getResource()
    {
        $this->processRouteSearch();
        return $this->currentResource;
    }

    public function setURI($uri)
    {
        $this->uri = $uri;
    }

    public function findURI($resource, $options = array())
    {

    }

    public function setRequestMethod($method = Request::METHOD_GET)
    {

        if (!isset(self::$validRequestMethods[$method])) {
            throw new RouterInvalidRequestMethodException(sprintf('Method `%s` is not a valid request method', $method));
        }
        $this->method = $method;
    }

    /**
     * Adds route to the callable resource
     * You can use '*' wildcard for the REQUEST_METHOD or ROUTE
     *
     * @param sring $route route to resource REQUEST_METHOD ROUTE eg
     *      GET items\:id
     *
     * @param type $callable
     *
     * @example
     * This will run closure when POST request will match given route
     * Router::addRoute('POST post/save', function ($route) {
     *      echo 'Adding new post';
     * });
     *
     * You can use parametrized routes by using dollar sign '$'
     * Router::addRoute('PUT post/$id', function($route){
     *      echo 'Editing post with id:' . $route->id;
     * });
     *
     */
    public function addResource($route, $resource)
    {
        $pos = strpos($route, ' ');
        if ($pos === false || $pos == 0) {
            $method = self::WILD_CARD;
            $path = $route;
        } else {
            $method = strtoupper(substr($route, 0, $pos));
            if (!isset(self::$validRequestMethods[$method])) {
                throw new RouterInvalidRequestMethodException(sprintf('Method `%s` is not a valid request method', $route[0]));
            }
            $path = substr($route, $pos + 1);
        }

        $resource = new Callback($resource);

        $this->routes[$method][$path] = $resource;
        $this->resources[$resource->getClassName()][$resource->getFunctionName()] = $route;
    }

    private function processRouteSearch()
    {
        if (!$this->forceMode && $this->currentRoute && $this->currentResource) {
            return true;
        }

        //search in provided method
        foreach ($this->routes[$this->method] as $route => $resource) {
            $r = new router\Route($route);
            //ommit not matching routes as well as wildcard the wildcard should
            //be checked on the same end
            if (!$r->isMatch($this->uri) || $route == self::WILD_CARD) {
                continue;
            }
            $this->currentResource = $resource;
            $this->currentResource->bindParameters($r->getParameters());
            $this->currentRoute = $r;
            return true;
        }

        //check if wildcard was set
        if (isset($this->routes[$this->method][self::WILD_CARD])) {
            $this->currentRoute = new router\Route(self::WILD_CARD);
            $this->currentResource = $this->routes[$this->method][self::WILD_CARD];
            return true;
        }


        //search in wildcard method
        if ($this->method == self::WILD_CARD) return false;
        foreach ($this->routes[self::WILD_CARD] as $route => $resource) {
            $r = new router\Route($route);
            if (!$r->isMatch($this->uri) || $route == self::WILD_CARD) {
                continue;
            }
            $this->currentResource = $resource;
            $this->currentResource->bindParameters($r->getParameters());
            $this->currentRoute = $r;
            return true;

        }

        //check if wildcard was set
        if (isset($this->routes[self::WILD_CARD][self::WILD_CARD])) {
            $this->currentRoute = new router\Route(self::WILD_CARD);
            $this->currentResource = $this->routes[self::WILD_CARD][self::WILD_CARD];
            return true;
        }

        return false;

    }

    const WILD_CARD = '*';

    protected static $validRequestMethods = array (
        self::WILD_CARD         => 1,
        Request::METHOD_POST    => 1,
        Request::METHOD_PUT     => 1,
        Request::METHOD_GET     => 1,
        Request::METHOD_DELETE  => 1
    );

    /**
     *
     * @var string
     */
    private $uri;

    /**
     *
     * @var string
     */
    private $method = Request::METHOD_GET;

    /**
     *
     * @var router\Route
     */
    private $currentRoute;

    /**
     *
     * @var \alchemy\app\Callback
     */
    private $currentResource;

    private $forceMode = false;

    /**
     *
     * @var array
     */
    private $routes = array(
        self::WILD_CARD => array(),
        Request::METHOD_POST    => array(),
        Request::METHOD_PUT     => array(),
        Request::METHOD_GET     => array(),
        Request::METHOD_DELETE  => array()
    );

    private $resources = array();
}