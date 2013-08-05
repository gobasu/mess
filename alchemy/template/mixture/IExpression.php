<?php
/**
 * Alchemy Framework (http://alchemyframework.org/)
 *
 * @link      http://github.com/dkraczkowski/alchemy for the canonical source repository
 * @copyright Copyright (c) 2012-2013 Dawid Kraczkowski
 * @license   https://raw.github.com/dkraczkowski/alchemy/master/LICENSE New BSD License
 */
namespace alchemy\template\mixture;
use alchemy\template\mixture\Compiler;
class ExpressionException extends \Exception {}
interface IExpression
{
    public static function isBlock();
    public static function getCloseTag();
    public static function getOpenTag();

    public function handle(Compiler $compiler);
}