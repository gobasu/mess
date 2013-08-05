<?php
/**
 * Alchemy Framework (http://alchemyframework.org/)
 *
 * @link      http://github.com/dkraczkowski/alchemy for the canonical source repository
 * @copyright Copyright (c) 2012-2013 Dawid Kraczkowski
 * @license   https://raw.github.com/dkraczkowski/alchemy/master/LICENSE New BSD License
 */
namespace alchemy\template\mixture\expression;

use alchemy\template\mixture\ExpressionException;
use alchemy\template\mixture\IExpression;
use alchemy\template\mixture\Node;
use alchemy\template\mixture\Compiler;
use alchemy\template\mixture\Template;

class VarExpression implements IExpression
{
    public function __construct(Node $node)
    {
        $this->node = $node;
    }

    public static function isBlock()
    {
        return false;
    }

    public static function getOpenTag()
    {
    }

    public static function getCloseTag()
    {
    }

    public function handle(Compiler $compiler)
    {
        $parameters = $this->node->getParameters();

        $var = self::getVariableReference($parameters[0]);

        if (in_array('strip', $parameters)) {
            $var = 'strip(' . $var . ')';
        }
        if (in_array('trim', $parameters)) {
            $var = 'trim(' . $var . ')';
        }

        //date
        if (in_array('date', $parameters)) {
            $dateFormat = Template::getOption(Template::OPTION_DATE_FORMAT);
            $var = 'is_numeric(' . $var . ') ? date(\'' . $dateFormat  . '\', ' . $var . ') : date(\'' . $dateFormat  . '\', strtotime(' . $var . '))';
        } elseif (in_array('datetime', $parameters)) {
            $dateFormat = Template::getOption(Template::OPTION_DATETIME_FORMAT);
            $var = 'is_numeric(' . $var . ') ? date(\'' . $dateFormat  . '\', ' . $var . ') : date(\'' . $dateFormat  . '\', strtotime(' . $var . '))';
        } else {
            $isCurrenty = in_array('currency', $parameters);
            if (in_array('number', $parameters) || $isCurrenty) {
                $numberFormat = Template::getOption(Template::OPTION_NUMBER_FORMAT);
                $var = 'number_format(' . $var . ', ' . $numberFormat[0] . ', \'' . $numberFormat[1] . '\', \'' . $numberFormat[2] . '\')';
                if ($isCurrenty) {
                    $var = $var . '.\' ' . Template::getOption(Template::OPTION_CURRENCY_SUFFIX) . '\'';
                }
            } elseif (!in_array('unescape', $parameters)) { //escape rest of the variables
                $var = 'htmlentities(' . $var . ', ENT_QUOTES, \'UTF-8\')';
            }
        }


        $compiler->appendText('<?php echo ' . $var . '?>');
    }

    public static function getVariableReference($name)
    {
        //current variable
        if ($name == '.' || $name == 'this' || $name == '$.') {
            return '$this->stack->get(\'.\')';
        }

        //loop variables
        if ($name{0} == '@') {
            $name = substr($name, 1);

            //undefined varname return null
            if (!isset(self::$loopVars[$name])) {
                return 'null';
            }
            return EachExpression::getVariable($name);
        }

        //normal variables from different expressions
        if ($name{0} == '$') {
            return '$this->stack->get(\''. substr($name, 1) . '\')';
        }

        //normal variables from var expression
        return '$this->stack->get(\''. $name . '\')';
    }

    /**
     * @var \alchemy\future\template\renderer\mixture\Node
     */
    protected $node;

    protected static $loopVars = array('odd' => true, 'even' => true, 'index' => true, 'value' => true, 'key' => true, 'last' => true, 'first' => true);
}
