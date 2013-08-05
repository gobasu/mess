<?php
/**
 * Alchemy Framework (http://alchemyframework.org/)
 *
 * @link      http://github.com/dkraczkowski/alchemy for the canonical source repository
 * @copyright Copyright (c) 2012-2013 Dawid Kraczkowski
 * @license   https://raw.github.com/dkraczkowski/alchemy/master/LICENSE New BSD License
 */
namespace alchemy\app;

class CallbackException extends \Exception {}
class CallbackNotExistsException extends CallbackException {}
class Callback
{
    /**
     * Creates callable object from given string, array or closure.
     * Both string and array can contain bindable values ex.
     *      MyInstance::${methodName}
     *      ${functionName}
     *      array ("ClassName", "${method}"
     * 
     * Than you can bind the parameters through the Resource::bindParameters
     * Callback::bindParameters(array('method' => 'myMethod'))
     *
     * Creating a callable object's method call
     * $callback = new Resource("MyClass->myFunction");
     *
     * Creating a callable static method call
     * $callback = new Resource("MyClass::myFunction");
     *
     * @param mixed $callable callable string, array or closure
     * @throws CallbackException when wrong parameter passed
     */
    public function __construct($callable)
    {
        if ($callable instanceof \Closure) {
            $this->isClosure = true;
            $this->resource = $callable;
            return;
        } elseif (is_array($callable)) {
            $this->isFunction = true;
            $this->className = $callable[0];
            $this->functionName = $callable[1];
            $this->resource = array(&$this->className, &$this->functionName);
        } elseif (is_string($callable) && !empty($callable)) {
            if (strstr($callable, self::INSTANCE_METHOD_SEPARATOR)) { //object
                $callable = explode(self::INSTANCE_METHOD_SEPARATOR, $callable);
                $this->isObject = true;
                $this->className = $callable[0];
                $this->functionName = $callable[1];
                $this->resource = array(&$this->className, &$this->functionName);
            } elseif (strstr($callable, self::CLASS_METHOD_SEPARATOR)) { //object
                $callable = explode(self::CLASS_METHOD_SEPARATOR, $callable);
                $this->className = $callable[0];
                $this->functionName = $callable[1];
                $this->resource = array(&$this->className, &$this->functionName);
            } else { //function name
                $this->isFunction = true;
                $this->functionName = $callable;
                $this->resource = &$this->functionName;
            }
            
        } else {
            throw new CallbackException('Expected argument to be string, closure or array,' . gettype($callable) . ' given');
        }
        
    }
    
    /**
     * Binds parameters to the resource
     * @example
     * function a() 
     * {
     *     echo 'give me an A';
     * }
     * $r = new Resource('{$myFunction}');
     * $r->bindParameters(array('myFunction' => 'a'));
     * if ($r->isCallable())
     * {
     *      call_user_func($r->getResource());//will echo 'give me an A';
     * }
     * 
     * @param array $parameters
     * @return type 
     */
    public function bindParameters(array $parameters)
    {
        //no binding for closures
        if ($this->isClosure) {
            return;
        }
        
        if ($this->functionName) {
            $this->functionName = $this->bindString($this->functionName, $parameters);
        }
        
        if ($this->className) {
            $this->className = $this->bindString($this->className, $parameters);
        }
        
        
    }
    
    /**
     * Determines whatever resource is callable or not
     *
     * @return true if resource is callable otherwise false
     */
    public function isCallable()
    {
        return is_callable($this->resource);
    }
    
    public function isClosure()
    {
        return $this->isClosure;
    }
    
    public function isFunction()
    {
        return $this->isFunction;
    }
    
    public function isObject()
    {
        return $this->isObject;
    }
    
    public function getClassName()
    {
        return $this->className;
    }
    
    public function getFunctionName()
    {
        return $this->functionName;
    }

    public function getResource()
    {
        return $this->resource;
    }

    public function setArguments(array $args)
    {
        $this->arguments = $args;
    }

    public function call(/** mutable **/)
    {
        $className = $this->getClassName();
        $functionName = $this->getFunctionName();
        $args = func_get_args();
        if (empty($args)) {
            $args = $this->arguments;
        }

        if ($this->isObject()) {
            if (!class_exists($className)) {
                throw new CallbackNotExistsException('Class ' . $className . ' does not exists');
            }
            $object = new $className();
            if (!method_exists($object, $functionName)) {
                throw new CallbackNotExistsException('Object ' . $className . ' has not got method ' . $functionName);
            }
            return call_user_func_array(array($object, $functionName), $args);
        }

        if (!is_callable($this->resource)) {
            throw new CallbackNotExistsException('Non callable resource');
        }

        return call_user_func($this->resource, $args);
    }

    private function bindString($string, $parameters)
    {
        //no bindables
        if (strstr($string, '$') === false) {
            return $string;
        }
        preg_match_all('#(\$\{([a-z0-9\-]+)\})#is', $string, $matches);

        $length = count($matches[0]);
        for ($i = 0; $i < $length; $i++) {
            if (!isset($parameters[$matches[2][$i]])) {
                continue;
            }
            $string = str_replace($matches[1][$i], $parameters[$matches[2][$i]], $string);
            
        }
        return $string;
    }

    const INSTANCE_METHOD_SEPARATOR = '->';
    const CLASS_METHOD_SEPARATOR = '::';

    private $className;
    private $functionName;
    private $isObject = false;
    private $isClosure = false;
    private $isFunction = false;
    private $resource;
    private $arguments = array();
}