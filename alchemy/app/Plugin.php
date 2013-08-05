<?php
/**
 * Alchemy Framework (http://alchemyframework.org/)
 *
 * @link      http://github.com/dkraczkowski/alchemy for the canonical source repository
 * @copyright Copyright (c) 2012-2013 Dawid Kraczkowski
 * @license   https://raw.github.com/dkraczkowski/alchemy/master/LICENSE New BSD License
 */
namespace alchemy\app;
use alchemy\event\EventHub;
use alchemy\event\EventDispatcher;
use alchemy\app\plugin\PluginLoader;
abstract class Plugin extends EventDispatcher implements plugin\IPlugin
{
    public function onLoad() {}
    public function onUnload() {}

    public function addListener($event, $listener)
    {
        EventHub::addListener($event, $listener);
        parent::addListener($event, $listener);
    }

    public function dispatch(\alchemy\event\Event $event)
    {
        EventHub::dispatch($event);
        parent::dispatch($event);
    }

    /**
     * Registers plugin
     */
    public static function register()
    {
        PluginLoader::_register(get_called_class());
    }
}