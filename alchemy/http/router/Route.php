<?php
/**
 * Alchemy Framework (http://alchemyframework.org/)
 *
 * @link      http://github.com/dkraczkowski/alchemy for the canonical source repository
 * @copyright Copyright (c) 2012-2013 Dawid Kraczkowski
 * @license   https://raw.github.com/dkraczkowski/alchemy/master/LICENSE New BSD License
 */
namespace alchemy\http\router;
/**
 * Route class
 * Handles and parses application's route regex information
 */
class Route 
{
    public function __construct($pattern)
    {
        if ($pattern == self::WILD_CARD) {
            $this->pattern = '.*';
            return;
        }
        
        $this->parseUrlPattern($pattern);
    }
    
    public function __get($param)
    {
        if (!isset($this->params[$param])) {
            return null;
        }
        
        return $this->params[$param];
    }

    public static function setSeparator($separator = '/')
    {
        self::$separator = $separator;
    }
    
    public function getPattern()
    {
        return $this->pattern;
    }
    
    public function getParameters()
    {
        return $this->params;
    }
    
    /**
     * Checks whatever route match given uri
     * 
     * @param string $uri 
     * @return true if uri match route's pattern
     */
    public function isMatch($uri)
    {
        $match = preg_match('#' . $this->regex . '#', $uri, $matches);
        if (!$match) {
            return false;
        }
        $length = count ($matches);
        $paramKeys = array_keys($this->params);
        //fetch params
        for ($i = 1; $i < $length; $i++) {
            $value = $matches[$i];
            $this->params[$paramKeys[$i - 1]] = $value;
        }
        
        return true;
    }
    
    private function parseUrlPattern($pattern)
    {
        $separator = self::$separator;
        $pattern = rtrim($pattern, $separator);
        $this->pattern = $pattern;
        //sanitize / signs
        $pattern = str_replace($separator, '\\' . $separator, $pattern);
        $route = $this;
        $pattern = preg_replace_callback('#' . self::PATTERN_REGEX . '#', function($match) use ($route) {
            if (isset($match[2])) {
                $regex = '(' . substr($match[2],1) . ')';
            } else {
                $regex = '([^\/]+?)';
            }
            $route->_registerParam($match[1]);
            unset($route);
            return $regex;
        }, $pattern);

        $this->regex = '^' . $pattern . '\/?$';
    }

    /**
     * Registers param within the route
     *
     * @param $paramName
     */
    public function _registerParam($paramName)
    {
        $this->params[$paramName] = null;
    }
    
    
    /**
     * Url pattern
     * @var string
     */
    private $regex;
    
    private $pattern;
    
    /**
     * List of arguments found in url pattern
     * @var array
     */
    private $params = array();

    protected static $separator = '/';
    
    const WILD_CARD = '*';
    const PATTERN_REGEX = '\{\$([a-z0-9\-]+)[\\\]?(\:[^\/]+)?\}';
    
}