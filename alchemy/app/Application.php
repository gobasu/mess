<?php
/**
 * Alchemy Framework (http://alchemyframework.org/)
 *
 * @link      http://github.com/dkraczkowski/alchemy for the canonical source repository
 * @copyright Copyright (c) 2012-2013 Dawid Kraczkowski
 * @license   https://raw.github.com/dkraczkowski/alchemy/master/LICENSE New BSD License
 */
namespace alchemy\app;

//define core dir
if (!defined('AL_CORE_DIR')) {
    define('AL_CORE_DIR', realpath(dirname(__FILE__) . '/../'));
}

//Register core loader
require_once AL_CORE_DIR . DIRECTORY_SEPARATOR . 'app' . DIRECTORY_SEPARATOR . 'Loader.php';
Loader::setup();

use alchemy\http\Router;
use alchemy\event\EventHub;
use alchemy\app\event\OnError;
use alchemy\app\event\OnShutdown;
use alchemy\app\Controller;
use alchemy\http\Request;
use alchemy\http\Response;
use alchemy\app\Loader;
use alchemy\storage\Session;

class ApplicationException extends \Exception {}
class ApplicationInvalidDirnameException extends ApplicationException {}
class ApplicationResourceNotFoundException extends ApplicationException {}
class Application
{
    /**
     * Creates new application
     *
     * @return Application
     */
    public static function instance()
    {
        if (self::$instance instanceof Application) {
            return self::$instance;
        }
        $class = get_called_class();
        return self::$instance = new $class;
    }

    /**
     * Constructor
     */
    protected function __construct()
    {
        \alchemy\event\EventHub::initialize();

        $this->router = new Router();
    }

    /**
     * Calls callback corresponding to route when matches
     * current url
     *
     * @see alchemy\http\Router::addResource
     *
     * @param $uri    uri pattern to given resource
     *                  for example GET /posts/{$id}
     * @param $handler
     */
    public function onURI($uri, $callback)
    {
        $this->router->addResource($uri, $callback);
    }

    /**
     * Sets application's root directory
     *
     * @param $dir
     * @throws ApplicationInvalidDirnameException
     */
    public function setApplicationDir($dir)
    {
        if (!is_dir($dir)) {
            throw new ApplicationInvalidDirnameException('Application dir does not exists');
        }
        define('AL_APP_DIR', $dir);

        $cacheDir =  AL_APP_DIR . '/cache';
        if (!is_writable($cacheDir)) {
            $cacheDir = sys_get_temp_dir();
        }

        define('AL_APP_CACHE_DIR', $cacheDir);

        Loader::register(function($className){
            $path = Loader::getPathForApplicationClass($className);
            if (is_readable($path)) {
                require_once $path;

                //provide onLoad static call for models
                if (is_subclass_of($className, 'alchemy\storage\Model')) {
                    $className::onLoad();
                }
            }
        });
    }

    /**
     * Turns on plugin system and set dir where plugin are
     *
     * @param string $dir
     */
    public function setPluginDir($dir)
    {
        $this->pluginDir = $dir;
    }

    public function getPluginDir($dir)
    {
        return $this->pluginDir;
    }

    /**
     * Sets directory for config loading
     *
     * @param $name
     * @throws ApplicationInvalidDirnameException if dir does not exists
     * @return true if dir was set otherwise false
     */
    public function setConfigDir($name)
    {
        //prevent changing dir after config is loaded
        if ($this->configLoader instanceof \alchemy\app\util\Config && $this->configLoader->isLoaded()) {
            return false;
        }

        $directory = AL_APP_DIR . '/' . $name;
        if (!is_dir($directory)) {
            throw new ApplicationInvalidDirnameException('Config dir `' . $directory . '` does not exist');
        }

        $this->configLoader = new \alchemy\app\util\Config($directory);
        return true;
    }

    public function onError($callable)
    {
        $this->onErrorHandler = new Callback($callable);
    }

    public function onStartup($callable)
    {
        $this->onStartupHandler = new Callback($callable);
    }

    /**
     * Gets config data if config dir was set
     */
    public function getConfig()
    {
        if ($this->configLoader && $this->configLoader instanceof \alchemy\app\util\Config) {
            if (!$this->configLoader->isLoaded()) {
                $this->configLoader->load();
            }
        } else {
            return false;
        }

        return $this->configLoader->getConfig();
    }

    /**
     * Gets config constant if config dir was set
     *
     * @param string $name
     */
    public function get($name)
    {
        if ($this->getConfig() === false) {
            return null;
        }

        return $this->configLoader->get($name);
    }

    /**
     * Runs application, handles request from global scope and translate them to fire up
     * right controller and method within the controller.
     * Unloads all loaded controllers of the end of execution
     *
     */
    public function run($mode = self::MODE_DEVELOPMENT)
    {
        if (!defined('AL_APP_DIR')) {
            throw new ApplicationException('No application dir set. Please use Application::setApplicationDir before use Application::run');
        }
        Session::start();
        $this->getConfig();

        //handle plugins
        if ($this->pluginDir) {
            \alchemy\app\plugin\PluginLoader::initialize($this->pluginDir);
        }


        $request = Request::getGlobal();

        $this->router->setRequestMethod($request->getMethod());
        $this->router->setURI($request->getURI());
        $match = $this->router->getRoute();
        $this->resource = $this->router->getResource();
        if ($this->onStartupHandler && $this->onStartupHandler->isCallable()) {
            $this->onStartupHandler->call();
        }

        if (!$match || !$this->resource->isCallable()) {
            $e = new ApplicationResourceNotFoundException('No callable resource found');
            EventHub::dispatch(new OnError($e));
            if ($this->onErrorHandler && $this->onErrorHandler->isCallable()) { //is app error handler registered
                $this->onErrorHandler->call($e);
                EventHub::dispatch(new OnShutdown($this));
                return false;
            } else {
                throw $e;
            }
        }
        $this->route = $match;

        try {
            //add execute listener
            ob_start();
            $this->executeResource();
            Controller::_unload();

        } catch (\Exception $e) {
            EventHub::dispatch(new OnError($e));
            if ($this->onErrorHandler && $this->onErrorHandler->isCallable()) { //is app error handler registered
                $this->onErrorHandler->call($e);
            } else {
                throw $e;
            }
        }
        EventHub::dispatch(new OnShutdown($this));
    }

    /**
     * Executes resource found in run method
     */
    protected function executeResource()
    {
        $resource = $this->resource;
        $className = $resource->getClassName();
        $functionName = $resource->getFunctionName();

        if ($resource->isObject()) {
            if (is_subclass_of($className, 'alchemy\app\Controller')) {
                $object = call_user_func(array($className,'load'));
            } else {
                $object = new $className;
            }
            $response = call_user_func(array($object, $functionName), $this->route->getParameters());
        } else {
            $response = call_user_func(array($resource, 'call'), $this->route->getParameters());
        }

        $contents = trim(ob_get_contents());
        ob_end_clean();

        //contents were echoed
        if ($contents) {
            $response = new Response($contents);
        } elseif (is_string($response)) {
            $response = new Response($response);
        } elseif ($response instanceof Response) {
            //do nothing
        } elseif($response == null) {
            $response = new Response('');
        } else {
            $responseType = get_class($response);
            throw new ApplicationException('Not a valid response type of ' . $responseType);
        }

        echo $response;
    }

    /**
     * @var \alchemy\app\Callback
     */
    protected $onErrorHandler;

    /**
     * @var \alchemy\app\Callback
     */
    protected $onStartupHandler;

    /**
     * @var \alchemy\http\Router
     */
    protected $router;

    /**
     * @var \alchemy\app\Callback
     */
    protected $resource;

    /**
     * @var \alchemy\app\util\Config
     */
    protected $configLoader;

    protected $mode = self::MODE_DEVELOPMENT;

    /**
     * @var Application
     */
    protected static $instance;

    /**
     * @var \alchemy\http\router\Route
     */
    protected $route;

    /**
     * @var string
     */
    protected $pluginDir;

    const MODE_DEVELOPMENT = 1;
    const MODE_PRODUCTION = 2;

    const VERSION = '0.9.6';
}