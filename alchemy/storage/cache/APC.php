<?php
/**
 * Copyright (C) 2012 Dawid Kraczkowski
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to
 * deal in the Software without restriction, including without limitation the
 * rights to use, copy, modify, merge, publish, distribute, sublicense, and/or
 * sell copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
 * INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR
 * A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF
 * CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE
 * OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */
namespace alchemy\storage\cache;

class APC implements IDriver
{
    public static function isAvailable()
    {
        return extension_loaded('apc');
    }

    public function get($key)
    {
        if (isset(self::$cache[$key])) return self::$cache[$key];
        return self::$cache[$key] = apc_fetch($key);
    }

    public function set($key, $value, $ttl = null)
    {
        self::$cache[$key] = $value;
        return apc_store($key, $value, $ttl);
    }

    public function delete($key)
    {
        unset(self::$cache[$key]);
        return apc_delete($key);
    }

    public function exists($key)
    {
        return apc_exists($key);
    }

    public function flush()
    {
        return apc_clear_cache(self::APC_USER_CACHE_NAME);
    }
    
    private static $cache = array();

    const APC_USER_CACHE_NAME = 'user';
}