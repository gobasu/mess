<?php
/**
 * Alchemy Framework (http://alchemyframework.org/)
 *
 * @link      http://github.com/dkraczkowski/alchemy for the canonical source repository
 * @copyright Copyright (c) 2012-2013 Dawid Kraczkowski
 * @license   https://raw.github.com/dkraczkowski/alchemy/master/LICENSE New BSD License
 */
namespace alchemy\template\mixture;

class VarStack
{
    public function __construct($data = array())
    {
        $this->data = $data;
        $this->current = $this->data;
    }

    public function get($varname)
    {
        if (!$varname || !$this->current) {
            return null;
        }

        if ($varname == '.') {
            return $this->current;
        }

        $return = $this->current;
        foreach(explode('.', $varname) as $path) {
            //check for null
            if ($return === null) {
                return null;
            }

            //handle string
            if (is_string($return)) {
                if ($path == 'length' || $path == 'size') {
                    $return = strlen($return);
                    continue;
                }
                if (is_numeric($path)) {
                    $return = $return{$path};
                    continue;
                }
                return null;
            }

            //handle array
            if (is_array($return)) {
                if (isset($return[$path])) {
                    $return = $return[$path];
                    continue;
                }

                if ($path == 'length' || $path == 'size') {
                    $return = count($return);
                    continue;
                }
                return null;
            }

            //handle object
            if (is_object($return)) {
                if (method_exists($return, $path) && is_callable(array($return, $path))) {
                    $return = $return->$path();
                    continue;
                }

                if (property_exists($return, $path)) {
                    $return = $return->$path;
                    continue;
                }

                if ($return instanceof ArrayAccess && $return->offsetExists($path)) {
                    $return = $return->offsetGet($path);
                    continue;
                }

                if (($path === 'length' || $path === 'size') && $return instanceof Countable) {
                    $return = count($return);
                    continue;
                }

                if (method_exists($return, '__isset') && $return->__isset($path)) {
                    $return = $return->$path;
                    continue;
                } elseif (method_exists($return, '__get')) {
                    $return = $return->$path;
                    continue;
                }
                return null;
            }
        }
        return $return;
    }

    public function in($var)
    {
        $this->stack[] = $var;
        $this->current = $this->get($var);

    }

    public function out()
    {
        array_pop($this->stack);
        if (empty($this->stack)) {
            $this->current = $this->data;
            return;
        }
        $this->current = $this->get(end($this->stack));
    }

    public function set($name, $value)
    {
        $this->current[$name] = $value;
    }

    public function reset()
    {
        $this->current = $this->data;
        if (empty ($this->stack)) {
            return;
        }
        $in = end($this->stack);
        $this->current = $this->get($in);
    }

    public function remove($name)
    {

    }

    protected $current;
    protected $data;
    protected $stack;
}
