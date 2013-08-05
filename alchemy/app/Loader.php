<?php
/**
 * Alchemy Framework (http://alchemyframework.org/)
 *
 * @link      http://github.com/dkraczkowski/alchemy for the canonical source repository
 * @copyright Copyright (c) 2012-2013 Dawid Kraczkowski
 * @license   https://raw.github.com/dkraczkowski/alchemy/master/LICENSE New BSD License
 */
namespace alchemy\app;

final class LoaderException extends \Exception {}
final class Loader
{
    /**
     * Setups framework autoloader
     */
    public static function setup()
    {
        self::register(function($className){
            $path = Loader::getPathForFrameworkClass($className);
            if (is_readable($path)) {
                require_once $path;
            }
        });
    }

    /**
     * Gets path for an user defined appllication's class
     *
     * @param string $className
     * @return string path to a class
     */
    public static function getPathForApplicationClass($className)
    {
        $className = substr($className, strpos($className, '\\'));

        $path = str_replace('\\', DIRECTORY_SEPARATOR, $className);
        return AL_APP_DIR . $path . '.php';
    }

    /**
     * Gets path for a framework core class
     *
     * @param string $className
     * @return string path to a class
     */
    public static function getPathForFrameworkClass($className)
    {
        //ommit first namespace element and replace \ with /
        $className = substr($className, strpos($className, '\\'));

        $path = str_replace('\\', DIRECTORY_SEPARATOR, $className);
        $path = AL_CORE_DIR . $path . '.php';
        return $path;
    }

    /**
     * Registers user defined loaders also used by Loader::setup
     *
     * @param $callable
     * @throws LoaderException when passed object is not callable
     */
    public static function register($callable)
    {
        if (!is_callable($callable)) {
            throw new LoaderException("Cannot register uncallable function as a loader");
        }
        spl_autoload_register($callable);
    }
}