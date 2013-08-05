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
 * Listener class used by EventDispatcher
 */
class Listener
{
    /**
     * @param mixed $listener callable
     */
    public function __construct($listener)
    {
        $this->listener = $listener;
    }

    /**
     * Calls listener
     *
     * @param Event $event
     */
    public function call(Event $event)
    {
        if (!$this->listener || !is_callable($this->listener)) return;
        call_user_func($this->listener, $event);
    }

    /**
     * Checks whatever passed listener is the same as this one
     *
     * @param $listener
     * @return bool
     */
    public function isA($listener)
    {
        return $listener == $this->listener;
    }
    
    private $listener;
}