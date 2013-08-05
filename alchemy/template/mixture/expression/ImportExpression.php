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

class ImportExpression implements IExpression
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
        return 'import';
    }

    public static function getCloseTag()
    {
    }

    public function handle(Compiler $compiler)
    {
        $parameters = $this->node->getParameters();
        $compiler->appendText('<?php $this->import(\'' . $parameters[1] . '\')?>');
    }

    /**
     * @var Node
     */
    protected $node;

}
