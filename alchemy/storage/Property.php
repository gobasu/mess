<?php
/**
 * Alchemy Framework (http://alchemyframework.org/)
 *
 * @link      http://github.com/dkraczkowski/alchemy for the canonical source repository
 * @copyright Copyright (c) 2012-2013 Dawid Kraczkowski
 * @license   https://raw.github.com/dkraczkowski/alchemy/master/LICENSE New BSD License
 */
namespace alchemy\storage;
/**
 * Property
 *
 */

class Property
{
    public function __construct($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getType()
    {
        return $this->type;
    }

    public function setType($type)
    {
        $this->type = $type;
    }

    public function setRequired($required = true)
    {
        $this->required = $required;
    }

    public function isRequired()
    {
        return $this->required;
    }

    public function __toString()
    {
        return (string) $this->localName;
    }

    private $type;
    private $name;
    private $required = false;

    const TYPE_BOOL = 0;
    const TYPE_NUMBER = 1;
    const TYPE_STRING = 2;
    const TYPE_BLOB = 3;
    const TYPE_ENUM = 4;
    const TYPE_DATE = 5;
    const TYPE_ARRAY = 6;
    const TYPE_JSON = 7;
}
