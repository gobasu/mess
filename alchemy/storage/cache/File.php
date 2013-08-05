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
class FileException extends \alchemy\storage\CacheException {}
class File implements IDriver
{

    /**
     * File oriented cache
     *
     * @param $filename
     */
    public function __construct ($filename)
    {
        if (!is_writable($filename)) {
            throw new FileException('Cache file `' . $filename . '` is not writeable!');
        }
        $this->filename = $filename;
        $this->data = json_decode(file_get_contents($filename), true);
    }

    public static function isAvailable()
    {
        return true;
    }

    public function get($key)
    {
        return isset($this->data[$key]) ?
            ($this->data[$key]['ttl'] < time() ? $this->data[$key]['value'] : $this->delete($key)) :
            null;
    }

    public function set($key, $value, $ttl = null)
    {
        $this->data[$key] = array('ttl' => time() + $ttl, 'value' => $value);
        $this->write();
    }

    public function delete($key)
    {
        unset($this->data[$key]);
        $this->write();
    }

    public function exists($key)
    {
        return isset($this->data[$key]) ? $this->data[$key]['ttl'] > time() : false;
    }

    public function flush()
    {
        $this->data = array();
        file_put_contents($this->filename, '{}');
    }

    protected function write()
    {
        file_put_contents($this->filename, json_encode($this->data));
    }

    private $filename;
    private $data = array();
}