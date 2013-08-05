<?php
/**
 * Alchemy Framework (http://alchemyframework.org/)
 *
 * @link      http://github.com/dkraczkowski/alchemy for the canonical source repository
 * @copyright Copyright (c) 2012-2013 Dawid Kraczkowski
 * @license   https://raw.github.com/dkraczkowski/alchemy/master/LICENSE New BSD License
 */
namespace alchemy\app;

use alchemy\object\ILoadable;
use alchemy\event\EventDispatcher;
use alchemy\event\EventHub;
use alchemy\event\Event;

class ControllerException extends \Exception {}

/**
 * Application's controller class 
 */
abstract class Controller extends EventDispatcher implements ILoadable
{
    /**
     * Called when controller is loaded by ControllerClassName::load()
     */
    public function onLoad()
    {
    }

    /**
     * Called when controller was unloaded by Application::run
     */
    public function onUnload()
    {
    }

    /**
     * Dispatches an event to EventHub
     *
     * @param \alchemy\event\Event $e
     */
    public function dispatch(Event $e)
    {
        EventHub::dispatch($e);
        parent::dispatch($e);
    }
    
    /**
     * Loads controller object
     * 
     * @return Controller
     */
    public static function load()
    {
        $class = get_called_class();
        
        if (isset(self::$loaded[$class])) {
            return self::$loaded[$class];
        }
        
        self::$loaded[$class] = new $class();
        self::$loaded[$class]->onLoad();
        return self::$loaded[$class];
    }
    
    /**
     * Unload previously loaded controllers
     */
    public static function _unload()
    {
        foreach (self::$loaded as $c) {
            $c->onUnload();
        }
    }
    
    private static $loaded = array();
}