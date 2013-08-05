<?php
/**
 * Alchemy Framework (http://alchemyframework.org/)
 *
 * @link      http://github.com/dkraczkowski/alchemy for the canonical source repository
 * @copyright Copyright (c) 2012-2013 Dawid Kraczkowski
 * @license   https://raw.github.com/dkraczkowski/alchemy/master/LICENSE New BSD License
 */
namespace alchemy\future\template\html\form;
class CheckBox extends Input
{
    public function __construct($label = '', Validator $validator = null)
    {
        parent::__construct($label, $validator);
    }

    public function __toString()
    {
        return sprintf(self::TEMPLATE, ($this->value == $this->chckValue ? 'checked="checked"' : ''), $this->chckValue, $this->attributesToString('value'));
    }


    const TEMPLATE = '<input type="checkbox" %s value="%s" %s />';
    private $chckValue = 1;
}