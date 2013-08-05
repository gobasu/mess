<?php
/**
 * Alchemy Framework (http://alchemyframework.org/)
 *
 * @link      http://github.com/dkraczkowski/alchemy for the canonical source repository
 * @copyright Copyright (c) 2012-2013 Dawid Kraczkowski
 * @license   https://raw.github.com/dkraczkowski/alchemy/master/LICENSE New BSD License
 */
namespace alchemy\app;

use alchemy\event\EventDispatcher;
use alchemy\event\EventHub;
use alchemy\event\Event;

class ViewException extends \Exception {}

/**
 * Application's View class
 */
abstract class View extends EventDispatcher
{
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

    public function __set($name, $value)
    {
        $this->vars[$name] = $value;
    }

    public function __get($name)
    {
        return isset($this->vars[$name]) ? $this->vars[$name] : null;
    }

    /**
     * Used to dump template
     * @return string
     */
    public function __toString()
    {
        try {
            $return = $this->render();
        } catch (\Exception $e) {
            $return = $e;
        }
        return (string) $return;
    }

    /**
     * Rendering logic goes here
     *
     * @return mixed
     */
    abstract public function render();

    /**
     * View vars
     * @var array
     */
    protected $vars = array();
}