<?php
/**
 * Alchemy Framework (http://alchemyframework.org/)
 *
 * @link      http://github.com/dkraczkowski/alchemy for the canonical source repository
 * @copyright Copyright (c) 2012-2013 Dawid Kraczkowski
 * @license   https://raw.github.com/dkraczkowski/alchemy/master/LICENSE New BSD License
 */
namespace alchemy\template\mixture;

class Node
{
    public function __construct($type = Node::NODE_TREE, $value = null)
    {
        $this->value = $value;
        $this->type = $type;
    }

    public function isRoot()
    {
        return $this->parent === null;
    }
    public function getParent()
    {
        return $this->parent;
    }

    public function setParent(Node $parent)
    {
        $this->parent = $parent;
    }

    public function addChild(Node $child)
    {
        $child->setParent($this);
        $this->children[] = $child;
    }

    public function addParameter($param)
    {
        $this->value .= ' ' . $param;
        $this->parameters[] = $param;
    }

    public function setValue($value)
    {
        $this->value = $value;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function getChildren()
    {
        return $this->children;
    }

    public function hasChildren()
    {
        return !empty($this->children);
    }

    public function getTagname()
    {
        if (!isset($this->parameters[0])) {
            return null;
        }
        return $this->parameters[0];
    }

    public function getType()
    {
        return $this->type;
    }

    public function getParameters()
    {
        return $this->parameters;
    }

    public function setHandler($className)
    {
        $this->handler = $className;
    }

    public function getHandler()
    {
        return $this->handler;
    }

    protected $value;
    protected $parameters = array();
    protected $isRoot = false;
    protected $type = -1;
    protected $handler;
    protected $parent = null;
    protected $children = array();


    const NODE_TREE = -1;
    const NODE_TEXT = 0;
    const NODE_VAR = 1;
    const NODE_TAG = 2;
}
