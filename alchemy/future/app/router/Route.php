<?php

namespace alchemy\future\app\router;
use alchemy\http\RouterException;

class RouteException extends \Exception {}
class Route
{
    /**
     * Creates new route
     * @param $pattern string
     * Pattern is a expression separated by Route::$separator eg
     * some/path
     *
     * Pattern can have defined variables in it. Variable should start from $ right after
     * separator and it ends when separator sign is met or with pattern's end
     * some/${path}
     *
     * Extensive patterns
     * post/${action}?/${id}? means the same as
     *      post
     *      post/${action}
     *      post/${action}/${id}
     *
     * Advanced patterns
     * post/${action:a-z0-9+}
     */
    public function __construct($pattern)
    {
        $this->pattern = trim($pattern, self::$separator);
    }

    /**
     * Finds out whatever this route matches given URI
     *
     * @param $uri string
     * @return bool
     */
    public function isMatch($uri)
    {
        $this->data = array();
        $this->parse();

        foreach ($this->matchers as $matcher) {
            if (preg_match('#^' . $matcher . '$#i', $uri, $values)) {
                if (!empty($values)) {
                    //remove route match
                    array_shift($values);

                    //create route data array if there are any params
                    if (!empty($values)) {
                        $length = count($values);
                        $this->data = array_combine(array_slice($this->params, 0, $length), $values);
                    }
                }
                return true;
            }
        }
        return false;
    }

    protected function parse()
    {
        if ($this->parsed) {
            return;
        }

        if ($this->pattern == self::WILD_CARD) {
            $this->matchers[] = '.*';
            return;
        }
        $parts = explode(self::$separator, $this->pattern);
        $regex = '';
        $paramCount = 0;
        foreach ($parts as $part) {
            //if part ends with ? add additional matcher to this route
            if (substr($part, -1) == '?') {
                $part = substr($part,0,-1);
                $this->matchers[] = $regex;
                $this->matchersParamCount[] = $paramCount;
            }
            $params = array();
            $part = preg_replace_callback('#' . self::PARAM_REGEX . '#', function($match) use (&$params, &$paramCount) {
                if (isset($match[2])) {
                    if (strpos($match[2], ')') != false || strpos($match[2] , '(') != false) {
                        throw new RouteException('Cannot use `(` & `)` in route pattern definition');
                    }
                    $regex = '(' . substr($match[2],1) . ')';
                } else {
                    $regex = '([^\/]+?)';
                }
                $params[] = $match[1];
                ++$paramCount;
                return $regex;
            }, $part);
            //escape separator for regex purpose
            $regex .= '\\' . self::$separator . $part;
            $this->params = array_merge($this->params, $params);
        }
        $this->matchersParamCount[] = $paramCount;
        $this->matchers[] = $regex;
        $this->parsed = true;
    }

    public function getData()
    {
        return $this->data;
    }

    private $pattern;
    private $params = array();
    private $matchers = array();
    private $matchersParamCount = array();
    private $parsed = false;
    private $data = array();

    protected static $separator = '/';

    const WILD_CARD = '*';
    const PARAM_REGEX = '\$\{([a-z0-9\-]+)(\:[^\/]+?)?\}';

}