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

class BlockExpression implements IExpression
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
        return 'block';
    }

    public static function getCloseTag()
    {
        return 'endblock';
    }

    public function handle(Compiler $compiler)
    {
        if ($this->node->getTagname() == self::getCloseTag()) {
            $compiler->gotoLastContext();
            return;
        }
        $parameters = $this->node->getParameters();

        if (!isset($parameters[1])) {
            throw new ExpressionException('Missing block name');
        }

        $func = 'userBlock' . self::sanitizeName($parameters[1]);
        $compiler->appendText('<?php $this->' . $func . '(); ?>');
        $compiler->setContext($func);
    }

    public static function sanitizeName($name)
    {
        return str_replace(array('-','.','/','+','*','&','^','#','@'), '_', $name);
    }

    /**
     * @var \alchemy\future\template\renderer\mixture\Node
     */
    protected $node;


}
