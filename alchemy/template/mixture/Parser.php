<?php
/**
 * Alchemy Framework (http://alchemyframework.org/)
 *
 * @link      http://github.com/dkraczkowski/alchemy for the canonical source repository
 * @copyright Copyright (c) 2012-2013 Dawid Kraczkowski
 * @license   https://raw.github.com/dkraczkowski/alchemy/master/LICENSE New BSD License
 */
namespace alchemy\template\mixture;

class ParserException extends \Exception
{
    public function __construct($message, Parser $p = null)
    {
        parent::__construct($message);
        if ($p) {
            $token = $p->getCurrentToken();
            $this->line = $token['line'] + 1;
            $this->column = $token['column'];
            $this->file = $p->getTokenizer()->getFile();
            $this->tokenizer = $p->getTokenizer();
        }
    }

    public function __toString()
    {
        $fileData = explode("\n", $this->tokenizer->getText());
        $context = '';
        if (isset($fileData[$this->line - 2])) {
            $context .= $fileData[$this->line - 2] . PHP_EOL;
        }
        if (isset($fileData[$this->line - 1])) {
            $context .= $fileData[$this->line - 1] . PHP_EOL;
        }
        $context .= sprintf("%'-" . ($this->column + 1) . "s\n", '^');

        $context .= $fileData[$this->line] . PHP_EOL;
        if (isset($fileData[$this->line + 1])) {
            $context .= $fileData[$this->line + 1] . PHP_EOL;
        }

        return 'MixtureException with message ' . $this->message . ' in ' . "\n" .  $context;
    }

    /**
     * @var Tokenizer
     */
    private $tokenizer;
    private $column;
}
class Parser
{
    public function __construct(Tokenizer $tokenizer)
    {
        $this->tokenizer = $tokenizer;
        $this->tree = new Node();

        //add default expressions
        $this->addExpression('\alchemy\template\mixture\expression\BlockExpression');
        $this->addExpression('\alchemy\template\mixture\expression\I18nExpression');
        $this->addExpression('\alchemy\template\mixture\expression\EachExpression');
        $this->addExpression('\alchemy\template\mixture\expression\IfExpression');
        $this->addExpression('\alchemy\template\mixture\expression\ImportExpression');
        $this->addExpression('\alchemy\template\mixture\expression\UseExpression');
        $this->addExpression('\alchemy\template\mixture\expression\ExtendExpression');
    }

    public function addExpression($className)
    {
        if (!class_exists($className, true)) {
            throw new ParserException('Expression handler class ' . $className . ' does not exists');
        }
        $open = $className::getOpenTag();
        $close = $className::getCloseTag();
        if ($open) {
            self::$openingExpressions[$open] = $className;
        }

        if ($close) {
            self::$closingExpressions[$close] = $className;
        }
        self::$blockExpressions[$open] = $className::isBlock();
        self::$blockExpressions[$close] = $className::isBlock();
        self::$openingTags[$close] = $open;
        self::$closingTags[$open] = $close;

    }

    /**
     * @return Tokenizer
     */
    public function getTokenizer()
    {
        return $this->tokenizer;
    }

    public function getCurrentToken()
    {
        return $this->token;
    }

    public function parse()
    {
        $tokens = $this->tokenizer->scan();
        $current = $this->tree;
        $next = null;
        $notExpecting = null;
        $expecting = null;

        foreach ($tokens as $token) {
            $this->token = &$token;
            //check if next token is expected one
            if ($expecting) {
                if (is_array($expecting) && !in_array($token['type'], $expecting)) {
                    throw new ParserException('Unexpected ' . $token['value'] . ', expecting one of: ' . implode(',', $expecting), $this);
                } elseif (!is_array($expecting) && $expecting != $token['type']) {
                    throw new ParserException('Unexpected ' . $token['value'] . ', expecting ' . $expecting, $this);
                }
            }

            if ($notExpecting) {
                if (is_array($notExpecting) && in_array($token['value'], $notExpecting)) {
                    throw new ParserException('Unexpected ' . $token['value'], $this);
                } elseif (!is_array($notExpecting) && $notExpecting == $token['type']) {
                    throw new ParserException('Unexpected ' . $token['value'], $this);
                }
            }

            //search for token
            if ($next && $next != $token['type']) {
                continue;
            }

            //reset
            $next = null;
            $expecting = null;
            $notExpecting = null;

            switch ($token['type']) {
                case Tokenizer::T_TEXT:
                    $current->addChild(new Node(Node::NODE_TEXT, $token['value']));
                    break;
                case Tokenizer::T_PARAM:
                    $current->addParameter($token['value']);
                    $expecting = array(Tokenizer::T_END_TAG, Tokenizer::T_PARAM, Tokenizer::T_END_VAR);
                    break;
                case Tokenizer::T_TAG:

                    $expecting = Tokenizer::T_PARAM;
                    $tag = new Node(Node::NODE_TAG, $token['value']);
                    $current->addChild($tag);
                    $current = $tag;
                    break;
                case Tokenizer::T_END_VAR:
                    $current->setValue($current->getValue() . ' ' . $token['type']);
                    $current = $current->getParent();
                    break;
                case Tokenizer::T_END_TAG:
                    $current->setValue($current->getValue() . ' ' . $token['type']);
                    $type = $current->getTagname();
                    if (!$this->isValidExpression($type)) {
                        //register helper expression
                        $current->setHandler('\alchemy\template\mixture\expression\HelperExpression');
                        $current = $current->getParent();
                        break;
                    }

                    if (!self::$blockExpressions[$type] || $current->isRoot()) {
                        $current->setHandler(self::$openingExpressions[$type]);
                        $current = $current->getParent();
                        break;
                    }

                    //nested block expression
                    if (isset(self::$closingExpressions[$type])) {
                        //enclosing tag
                        $lastOpened = self::$openingTags[$type];
                        $current->setHandler(self::$closingExpressions[$type]);
                        $current = $current->getParent()->getParent();

                        if (end($this->openTags) != $lastOpened) {
                            $expected = self::$closingTags[end($this->openTags)];
                            throw new ParserException('Nesting conflict, expected "' . $expected . '", got "' . $type . '"', $this);
                        }
                        array_pop($this->openTags);
                    } else {
                        $current->setHandler(self::$openingExpressions[$type]);
                        $this->openTags[] = $type;
                    }
                    break;
                case Tokenizer::T_VAR:
                    $tag = new Node(Node::NODE_VAR, $token['value']);
                    $tag->setHandler(self::VAR_HANDLER);
                    $current->addChild($tag);

                    $current = $tag;
                    break;
            }
        }

        //parser was expecting a token none given
        if (!empty($this->openTags)) {
            throw new ParserException('Unexpected end of template file, expecting: ' . self::$closingTags[end($this->openTags)], $this);
        }

        return $this->tree;
    }

    private function isValidExpression($type)
    {
        return isset(self::$openingExpressions[$type]) || isset(self::$closingExpressions[$type]);
    }



    protected $tree;
    protected static $openingExpressions = array();
    protected static $closingExpressions = array();
    protected static $blockExpressions = array();
    protected static $openingTags = array();
    protected static $closingTags = array();
    protected $token;
    /**
     * @var Tokenizer
     */
    protected $tokenizer;

    private $openTags = array();


    const BLOCK_END_EXPR = 'end';
    const VAR_HANDLER = '\alchemy\template\mixture\expression\VarExpression';
}
