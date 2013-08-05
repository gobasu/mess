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

class EachExpression implements IExpression
{
    public function __construct(Node $node)
    {
        $this->node = $node;
    }

    public static function isBlock()
    {
        return true;
    }

    public static function getOpenTag()
    {
        return 'each';
    }

    public static function getCloseTag()
    {
        return 'endeach';
    }

    public function handle(Compiler $compiler)
    {
        if ($this->node->getTagname() == self::getCloseTag()) {
            array_pop(self::$iteratedItems);
            $compiler->appendText('<?php endforeach; endif;?>');

            return;
        }

        $parameters = $this->node->getParameters();
        if ($parameters[2] != 'in') {
            throw new CompilerException('Used unknown expression ' . $parameters[2] . ' in ' . $parameters[0] . ' tag');
        }

        if ($parameters[1]{0} == '$') {
            $parameters[1] = substr($parameters[1], 1);
        }
        self::$iteratedItems[] = $parameters[1];

        $index = self::getVariable('index');
        $key = self::getVariable('key');
        $value = self::getVariable('value');
        $odd = self::getVariable('odd');
        $even = self::getVariable('even');
        $last = self::getVariable('last');
        $first = self::getVariable('first');
        $length = self::getVariable('length');

        //looping through a variable
        if ($parameters[3]{0} == '$') {
            $var = VarExpression::getVariableReference($parameters[3]);
            $compiler->appendText('<?php if(is_array(' . $var. ')): ' . $index . ' = 0; ' . $length . ' = count(' . $var . '); foreach(' . $var . ' as ' . $key . ' => ' . $value . '):');
            $compiler->appendText('$this->stack->set(\'' . $parameters[1] . '\', ' . $value . ');');
            $compiler->appendText(
                $first . ' = ' . $index. ' == 0 ? true : false;' .
                $last . ' = ' . $length . ' - 1 == ' . $index . ' ? true : false;' .
                $index . '++;' .
                $odd . ' = ' . $index . '%2 ? false : true;' .
                $even . ' = !' . $odd . '; ' .

            '?>');
            return;
        }

        if (!preg_match('#^(\d+)\.\.(\d+)$#', $parameters[3], $matches)) {
            throw new CompilerException('Used unknown expression ' . $parameters[3] . ' in ' . $parameters[0] . ' tag');
        }
        $range = self::getVariable('range');


        $compiler->appendText('<?php if(true): ' . $index .' = 0; ' . $range .' = range(' . $matches[1] . ',' . $matches[2] . ');' . $length . ' = count(' . $range . '); foreach(' . $range . ' as ' . $key . ' => ' . $value . '):');
        $compiler->appendText('$this->stack->set(\'' . $parameters[1] . '\', ' . $value . ');');
        $compiler->appendText(
            $first . ' = ' . $index. ' == 0 ? true : false;' .
            $last . ' = ' . $length . ' - 1 == ' . $index . ' ? true : false;' .
            $index . '++;' .
            $odd . ' = ' . $index . '%2 ? false : true;' .
            $even . ' = !' . $odd . '; ' .
        '?>');

    }

    public static function getVariable($type, $iteratedItem = null)
    {
        return '$_each' . $type . '_' . ($iteratedItem ? $iteratedItem : end(self::$iteratedItems));
    }

    public static function isIterationAvailable($name)
    {
        return in_array($name, self::$iteratedItems);
    }


    /**
     * @var \alchemy\future\template\renderer\mixture\Node
     */
    protected $node;

    protected static $rangeNumber = 0;

    /**
     * Disalows to mix scopes
     * @var int
     */
    protected static $iteratedItems = array();

}
