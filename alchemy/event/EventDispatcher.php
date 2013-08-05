<?php
/**
 * Alchemy Framework (http://alchemyframework.org/)
 *
 * @link      http://github.com/dkraczkowski/alchemy for the canonical source repository
 * @copyright Copyright (c) 2012-2013 Dawid Kraczkowski
 * @license   https://raw.github.com/dkraczkowski/alchemy/master/LICENSE New BSD License
 */
namespace alchemy\event;

/**
 * EventDispacher class
 * Observer
 */
class EventDispatcher
{
    /**
     * Add Event Listener
     *
     * @param string $event class name of event you are listening at
     * @param callable $listener callable function call eg. array('YourClass','method')
     * 
     * @example
     * $e = new EventDispatcher();
     * $e->addListener('OnError', function($evt) {
     *  print_r($evt);
     *  echo 'Event appeared';
     * });
     */
    public function addListener($event, $listener)
    {
        if (!isset($this->listeners[$event])) {
            $this->listeners[$event] = array();
        }
        $this->listeners[$event][] = new Listener($listener);
    }

    /**
     * Removes event listener
     *
     * @param string $event event class name
     * @param callable $listener 
     * @return boolean
     */
    public function removeListener($event, $listener)
    {
        if (!isset($this->listeners[$event])) {
            return;
        }
        foreach ($this->listeners[$event] as $k => &$l)
        {
            if (!$l->isA($listener)) {
                continue;
            }
            unset ($this->listeners[$event][$k]);
        }
    }

    /**
     * Check if given object has event listener
     *
     * @param string $event
     * @param function $listener
     * @return boolean
     */
    public function hasListener($event, $listener)
    {
        if (!isset($this->listeners[$event])) {
            return false;
        }
        foreach ($this->listeners[$event] as $k => &$l)
        {
            if (!$l->isA($listener)) {
                continue;
            }
            return true;
        }
        return false;
    }

    /**
     * Dispatch event supports bubbling and propaging
     *
     * @param Event $event
     * @return boolean false if no listeners were executed otherwise true
     */
    public function dispatch(Event $event)
    {
        $className = get_class($event);
        $list = class_parents($className);

        array_unshift($list, $className);
        $listenerExist = false;

        foreach ($list as $eventClass) {
            if (!$event->_isBubbling()) {
                break;
            }
            if (!isset($this->listeners[$eventClass])) {
                continue;
            }
            foreach ($this->listeners[$eventClass] as $listener) {
                $listener->call($event);
                $listenerExist = true;
                if (!$event->_isPropagating()) {
                    break 2;
                }
            }
        }
        return $listenerExist;
    }

    /**
     * @param array
     */
    protected $listeners = array();

}
