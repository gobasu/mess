<?php
/**
 * Alchemy Framework (http://alchemyframework.org/)
 *
 * @link      http://github.com/dkraczkowski/alchemy for the canonical source repository
 * @copyright Copyright (c) 2012-2013 Dawid Kraczkowski
 * @license   https://raw.github.com/dkraczkowski/alchemy/master/LICENSE New BSD License
 */
namespace alchemy\future\template\html\form;
class SelectList extends Input
{
    public function setData($list)
    {
        $this->list = $list;
    }

    public function getData()
    {
        return $this->list;
    }

    public function __toString()
    {
        $selectValue = $this->getValue();
        $options = '';
        foreach ($this->list as $name => $value)
        {
            if (is_array($value))
            {
                $options .= '<optgroup label="' . $name . '">';
                foreach($value as $optgroupName => $optgroupValue)
                {
                    $options .= '<option ' . ($selectValue == $optgroupName ? 'selected="selected"' : '') . ' value="' . $optgroupName  . '">' . $optgroupValue . '</option>';
                }
                $options .= '</optgroup>';
                continue;
            }

            $options .= '<option ' . ($selectValue == $name ? 'selected="selected"' : '') . ' value="' . $name  . '">' . $value . '</option>';
        }
        //return '';
        return sprintf(self::TEMPLATE, $this->attributesToString('value'), $options);
    }

    private $list = array();
    const TEMPLATE = '<select %s>%s</select>';
}