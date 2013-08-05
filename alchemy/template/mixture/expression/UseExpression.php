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

class UseExpression implements IExpression
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
        return 'use';
    }

    public static function getCloseTag()
    {
        return 'enduse';
    }

    public function handle(Compiler $compiler)
    {
        $parameters = $this->node->getParameters();
        if ($this->node->getTagname() == self::getCloseTag()) {
            $compiler->appendText('<?php endif; $this->stack->out();?>');
            return;
        }
        if ($parameters[1]{0} != '$') {
            throw new CompilerException('Cannot use keyword as variable in ' . $parameters[0] . ' expression');
        }
        $compiler->appendText('<?php $this->stack->in(\'' . substr($parameters[1], 1) . '\'); if($this->get(\'.\')):?>');
    }

    /**
     * @var \alchemy\future\template\renderer\mixture\Node
     */
    protected $node;


}
