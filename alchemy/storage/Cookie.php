<?php
/**
 * Alchemy Framework (http://alchemyframework.org/)
 *
 * @link      http://github.com/dkraczkowski/alchemy for the canonical source repository
 * @copyright Copyright (c) 2012-2013 Dawid Kraczkowski
 * @license   https://raw.github.com/dkraczkowski/alchemy/master/LICENSE New BSD License
 */
namespace alchemy\storage;

class CookieException extends \Exception {}
/**
 * Cookie handler class
 */
class Cookie
{
    /**
     * Alias for Cookie::set
     * @see Cookie::set
     */
    public function __set($name, $value)
    {
        self::set($name, $value);
    }

    /**
     * Sets cookie
     * @param $name cookie's name
     * @param $value cookie's value
     * @param int $expiration cookie's expiration name
     * @throws CookieException
     */
    public static function set($name, $value, $expiration = null)
    {
        if ($expiration) {
            $expiration = time() + $expiration;
        }
        $_COOKIE[$name] = $value;
        if (!is_array($value)) {
            setcookie($name, $value, $expiration, '/');
            return;
        }

        foreach ($value as $key => $v) {
            if (is_array($v)) {
                throw new CookieException('Invalid cookie value!');
            }
            setcookie(sprintf('%s[%s]', $name, $key), $v, $expiration, '/');
        }
    }

    /**
     * Alias for Cookie::get
     * @see Cookie::get
     */
    public function &__get($name)
    {
        $p = self::get($name);
        return $p;
    }

    /**
     * Gets cookie
     * @param $name cookie name
     * @return mixed
     */
    public static function &get($name)
    {
        $var = isset($_COOKIE[$name]) ? $_COOKIE[$name] : null;
        return $var;
    }
}