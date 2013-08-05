<?php
/**
 * Alchemy Framework (http://alchemyframework.org/)
 *
 * @link      http://github.com/dkraczkowski/alchemy for the canonical source repository
 * @copyright Copyright (c) 2012-2013 Dawid Kraczkowski
 * @license   https://raw.github.com/dkraczkowski/alchemy/master/LICENSE New BSD License
 */
namespace alchemy\future\template\html\form;

abstract class Input
{
    public function __construct($label = '', Validator $validator = null)
    {
        $this->label = $label;
    }

    public function setCSS($css)
    {
        $this->style = $css;
    }

    public function addCSSClass($class)
    {
        $class = explode(' ', $class);
        $diff = array_diff($class, $this->css);
        $this->css = array_merge($this->css, $diff);
        $this->attributes['class'] = implode(' ', $this->css);

    }

    public function removeCSSClass($class)
    {
        if ($class == '*') {
            if (isset($this->attributes['css'])) {
                unset($this->attributes['css']);
            }
            return $this->css = array();
        }
        $class = explode(' ', $class);
        foreach ($class as $c) {
            $key = array_search($c, $this->css);
            if ($key) {
                unset($this->css[$key]);
            }
        }
    }

    public function setId($id)
    {
        $this->id = $id;
        $this->attributes['id'] = $id;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setValidator(Validator $validator, $errorMessage = null)
    {
        if ($errorMessage) {
            $validator->setMessage(htmlspecialchars($errorMessage));
        }
        $this->validator = $validator;
    }

    public function getValidator()
    {
        return $this->validator;
    }

    public function validate()
    {
        $this->isValidated = true;
        if (!$this->validator) {
            $this->isValid = true;
            return true;
        }
        if (!$this->validator->validate($this->value)) {
            $this->isValid = false;
            $this->attributes['data-on-error'] = $this->errorMessage;
            $this->addCSSClass('OnError');
            return false;
        }
        $this->isValid = true;
        $this->addCSSClass('OnSuccess');
        return true;
    }

    public function getValue()
    {
        return $this->value;
    }

    public function setValue($value)
    {
        $value = htmlentities($value);
        $this->value = $value;
        $this->attributes['value'] = $value;
    }

    public function setName($name)
    {
        $this->name = $name;
        $this->attributes['name'] = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function setAttribute($attr, $value)
    {
        $attr = strtolower((string)$attr);
        $value = (string)$value;
        switch ($attr) {
            case 'style':
            {
                $this->setCSS($value);
                break;
            }
            case 'class':
            {
                $value = explode(' ', $value);
                $this->css = $value;
                $this->attributes['css'] = $value;
                break;
            }
            case 'id':
            {
                $this->setId($value);
                break;
            }
            case 'name':
            {
                $this->setName($value);
                break;
            }
            case 'value':
            {
                $this->setValue($value);
                break;
            }
            default:
                {
                if (!$value) unset($this->attributes[$attr]);
                $this->attributes[$attr] = $value;
                break;
                }
        }

    }

    public function getLabel()
    {
        return $this->label;
    }

    public function setLabel($label)
    {
        return $this->label = $label;
    }

    public function getAttribute($attr)
    {
        if (!key_exists($attr, $this->attributes)) return null;
        return $this->attributes[$attr];
    }

    protected function attributesToString($exclude = array())
    {
        if (!is_array($exclude)) {
            $exclude = array($exclude);
        }
        $string = '';
        foreach ($this->attributes as $attr => $value) {
            if (in_array($attr, $exclude)) continue;
            $string .= ' ' . $attr . '="' . addslashes($value) . '"';
        }
        return $string;
    }

    protected $css = array();
    protected $style;
    protected $id;
    /**
     * @var Validator
     */
    protected $validator;
    protected $isValidated;
    protected $isValid;
    protected $value;
    protected $name;
    protected $attributes = array();
    protected $errorMessage;
    protected $label;
}
