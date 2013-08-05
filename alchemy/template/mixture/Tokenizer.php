<?php
/**
 * Alchemy Framework (http://alchemyframework.org/)
 *
 * @link      http://github.com/dkraczkowski/alchemy for the canonical source repository
 * @copyright Copyright (c) 2012-2013 Dawid Kraczkowski
 * @license   https://raw.github.com/dkraczkowski/alchemy/master/LICENSE New BSD License
 */
namespace alchemy\template\mixture;

//use alchemy\future\template\renderer\MixtureException;

class TokenizerException extends \Exception {}

/**
 * Very simple tokenizer class
 */
class Tokenizer
{
    public function __construct($file)
    {
        if (!is_readable($file)) {
            throw new TokenizerException('File is not readable ' . $file);
        }
        $this->file = $file;
        $this->text = file_get_contents($file);
    }

    public function scan()
    {
        $length = strlen($this->text);
        $buffer = '';
        $stringDelimiter = '';
        $inVar = false;
        $prevC = null;


        for (; $this->index < $length; $this->index++) {
            $this->column++;
            if ($this->index > 0) {
                $prevC = $this->text[$this->index - 1];
            }
            $c = $this->text[$this->index];
            if ($this->index + 1 < $length) {
                $token = $c . $this->text[$this->index + 1];
            }

            if ($c == PHP_EOL) {
                $this->line++;
                $this->column = 0;
            }

            switch ($this->state) {
                case self::S_TEXT://we are in text

                    if (isset(self::$oTokens[$token])) {
                        $this->addToken(self::T_TEXT, $buffer);
                        $buffer = '';
                    }
                    switch ($token) {
                        case self::T_TAG:
                            $this->state = self::S_TAG;
                            $this->addToken(self::T_TAG, $token);
                            $this->index++;
                            continue 3;
                        case self::T_VAR:
                            $inVar = true;
                            $this->state = self::S_TAG;
                            $this->addToken(self::T_VAR, $token);
                            $this->index++;

                            continue 3;
                        case self::T_IGNORE:
                            $this->state = self::S_IGNORE;
                            $this->addToken(self::T_IGNORE, $token);
                            $this->index++;
                            continue 3;
                    }
                    break;

                case self::S_TAG://we are in middle of tag
                    if (ctype_space($c)) {//ommit spaces
                        continue 2;
                    } elseif ($inVar && $c == '}') {
                        $this->addToken(self::T_END_VAR, $c);
                        $inVar = false;
                        $buffer = '';
                        $this->state = self::S_TEXT;
                        continue 2;
                    } elseif (!$inVar && $token == self::T_END_TAG) {
                        $this->addToken(self::T_END_TAG, $token);
                        $buffer = '';
                        $this->state = self::S_TEXT;
                        $this->index++;
                        continue 2;
                    }

                    $this->state = self::S_PARAM;
                case self::S_PARAM://we are in param
                    if ($c == '"' || $c == '\'') {
                        $this->state = self::S_STRING;
                        $stringDelimiter = $c;
                        $buffer = '';
                        continue 2;
                    }

                    if (ctype_space($c)) {
                        $this->addToken(self::T_PARAM, $buffer);
                        $buffer = '';
                        $this->state = self::S_TAG;
                        continue 2;
                    }

                    if ($inVar && $c == self::T_END_VAR) {//end var if no space
                        //add latest param
                        if ($buffer) {
                            $this->addToken(self::T_PARAM, $buffer);
                            $buffer = '';
                        }
                        $this->addToken(self::T_END_VAR, $c);
                        $this->state = self::S_TEXT;
                        $inVar = false;
                        continue 2;
                    }

                    if (!$inVar && $token == self::T_END_TAG) {
                        $this->index++;
                        //add latest param
                        if ($buffer) {
                            $this->addToken(self::T_PARAM, $buffer);
                            $buffer = '';
                        }
                        $this->addToken(self::T_END_TAG, $token);
                        $this->state = self::S_TEXT;
                        continue 2;
                    }


                    break;
                case self::S_STRING://param as a string
                    if ($c == $stringDelimiter && $prevC != '\\') {
                        $this->addToken(self::T_PARAM, $buffer);
                        $buffer = '';
                        $this->state = self::S_TAG;
                        continue 2;
                    }
                    break;
                case self::S_IGNORE://we are in ignore mode
                    if ($token == self::T_END_IGNORE) {
                        $this->addToken(self::T_TEXT, $buffer);
                        $buffer = '';
                        $this->addToken(self::T_END_IGNORE, $token);
                        $this->state = self::S_TEXT;
                        $this->index++;
                        continue 2;
                    }
                    break;
            }

            $buffer .= $c;
        }

        if ($buffer) {
            $this->addToken(self::T_TEXT, $buffer);
        }

        return $this->tokens;
    }

    public function getTokens()
    {
        return $this->tokens;
    }

    public function getText()
    {
        return $this->text;
    }

    public function getFile()
    {
        return $this->file;
    }

    protected function addToken($type, $value)
    {
        $this->tokens[] = array(
            'type'  => $type,
            'value' => $value,
            'column'=> $this->column,
            'line'  => $this->line,
            'index' => $this->index
        );
    }

    protected $state = self::S_TEXT;
    protected $line = 0;
    protected $index = 0;
    protected $column = 0;
    protected $tokens = array();

    //opening tokens
    protected static $oTokens = array(
        self::T_TAG     => true,
        self::T_VAR     => true,
        self::T_IGNORE  => true
    );

    protected $file;
    protected $text;


    //tag types
    const T_TAG = '{%';
    const T_END_TAG = '%}';

    const T_VAR = '${';
    const T_END_VAR = '}';

    const T_IGNORE = '{!';
    const T_END_IGNORE = '!}';


    //token types
    const T_TEXT = 'text';
    const T_PARAM = 'param';


    //states
    const S_TEXT = 0;
    const S_TAG = 1;
    const S_PARAM = 2;
    const S_STRING = 3;
    const S_IGNORE = 4;
}
