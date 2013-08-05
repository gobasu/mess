<?php
/**
 * Alchemy Framework (http://alchemyframework.org/)
 *
 * @link      http://github.com/dkraczkowski/alchemy for the canonical source repository
 * @copyright Copyright (c) 2012-2013 Dawid Kraczkowski
 * @license   https://raw.github.com/dkraczkowski/alchemy/master/LICENSE New BSD License
 */
namespace alchemy\storage;
class CacheException extends \Exception {}
class CacheDriverNotFoundException extends CacheException {}

class Cache
{
    /**
     * Adds cache driver new added driver is treated like a default one
     *
     * @param cache\IDriver $driver
     * @param string $name driver name
     */
    public static function addDriver(cache\IDriver $driver, $name = self::DEFAULT_DRIVER_NAME)
    {

        $driverName = $name ? $name : (method_exists($driver, 'getName') ? $driver->getName() : get_class($driver));
        self::$driverList[$driverName] = $driver;
        self::$currentDriver = $driver;

    }

    /**
     * Sets driver as default one
     * @param string $name
     * @throws CacheDriverNotFoundException
     */
    public static function useDriver($name)
    {
        if (!isset(self::$driverList[$name])) throw new CacheDriverNotFoundException(sprintf('You have to add driver first before using it, use %s'), __CLASS__ . '::addDriver()');
        if (self::$currentDriver instanceof cache\IDriver)
        {
            self::$previousDriver = self::$currentDriver;
        }
        self::$currentDriver = self::$driverList[$name];
    }

    /**
     * Restores previous driver as default one
     * @return bool true if driver was changes or false if not
     */
    public static function restorePreviousDriver()
    {
        if (!(self::$previousDriver instanceof cache\IDriver)) return false;
        self::$currentDriver = self::$previousDriver;
        self::$previousDriver = null;
        return true;
    }


    public static function set($key, $value, $ttl = null, $driverName = null)
    {
        return self::getDriver($driverName)->set($key, $value, $ttl);
    }

    public static function get($key, $driverName = null)
    {
        return self::getDriver($driverName)->get($key);
    }

    public static function delete($key, $driverName = null)
    {
        return self::getDriver($driverName)->delete($key);
    }

    public static function flush($driverName = null)
    {
        return self::getDriver($driverName)->flush();
    }

    /**
     *
     * @param type $name
     * @return cache\IDriver
     */
    public static function getDriver($name = null)
    {
        if (!(self::$currentDriver instanceof cache\IDriver)) self::$currentDriver = new cache\Dummy();
        if (!$name) return self::$currentDriver;
        if (!isset(self::$driverList[$name])) throw new CacheDriverNotFoundException(sprintf('You have to add driver first before using it, use %s'), __CLASS__ . '::addDriver()');
        return self::$driverList[$name];
    }



    /**
     * Array of
     * @var cache\IDriver
     */
    private static $driverList = array();

    /**
     * Instance of
     * @var cache\IDriver
     */
    private static $currentDriver;

    /**
     * Instance of
     * @var cache\IDriver
     */
    private static $previousDriver;


    const DEFAULT_DRIVER_NAME = 'DefaultCacheDriver';
}