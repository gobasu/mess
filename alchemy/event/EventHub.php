<?php
/**
 * Alchemy Framework (http://alchemyframework.org/)
 *
 * @link      http://github.com/dkraczkowski/alchemy for the canonical source repository
 * @copyright Copyright (c) 2012-2013 Dawid Kraczkowski
 * @license   https://raw.github.com/dkraczkowski/alchemy/master/LICENSE New BSD License
 */
namespace alchemy\event;

class EventHub
{
    /**
     * Initializes application event hub
     *
     * @return bool true if event hub was previously initialized
     */
    public static function initialize()
    {
        if (self::$isInitialized) {
            return self::$isInitialized;
        }
        self::$dispatcher = new EventDispatcher();
        self::$isInitialized = true;
    }

    /**
     * Dispatches an event through all hub's listeners
     *
     * @see EventDispatcher::dispatch
     */
    public static function dispatch(Event $event)
    {
        return self::$dispatcher->dispatch($event);
    }

    /**
     * Adds listener to the event hub
     *
     * @see EventDispatcher::addListener
     */
    public static function addListener($event, $listener)
    {

        return self::$dispatcher->addListener($event, $listener);
    }

    /**
     *
     * @see EventDispatcher::hasListener
     */
    public static function hasListener($event, $listener)
    {
        return self::$dispatcher->hasListener($listener);
    }

    /**
     *
     * @see EventDispatcher::removeListener
     */
    public static function removeListener($event, $listener)
    {
        return self::$dispatcher->removeListener($event, $listener);
    }
    
    /**
     * 
     * @var alchemy\event\EventDispatcher
     */
    private static $dispatcher;
    
    /**
     *
     * @var boolean
     */
    private static $isInitialized = false;
}