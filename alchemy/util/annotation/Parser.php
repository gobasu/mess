<?php
/**
 * Copyright (C) 2012 Dawid Kraczkowski
 *
 * Permission is hereby granted, free of charge, to any person obtaining a copy
 * of this software and associated documentation files (the "Software"), to
 * deal in the Software without restriction, including without limitation the
 * rights to use, copy, modify, merge, publish, distribute, sublicense, and/or
 * sell copies of the Software, and to permit persons to whom the Software is
 * furnished to do so, subject to the following conditions:
 *
 * The above copyright notice and this permission notice shall be included in all
 * copies or substantial portions of the Software.
 *
 * THE SOFTWARE IS PROVIDED "AS IS", WITHOUT WARRANTY OF ANY KIND, EXPRESS OR IMPLIED,
 * INCLUDING BUT NOT LIMITED TO THE WARRANTIES OF MERCHANTABILITY, FITNESS FOR
 * A PARTICULAR PURPOSE AND NONINFRINGEMENT. IN NO EVENT SHALL THE AUTHORS OR COPYRIGHT
 * HOLDERS BE LIABLE FOR ANY CLAIM, DAMAGES OR OTHER LIABILITY, WHETHER IN AN ACTION OF
 * CONTRACT, TORT OR OTHERWISE, ARISING FROM, OUT OF OR IN CONNECTION WITH THE SOFTWARE
 * OR THE USE OR OTHER DEALINGS IN THE SOFTWARE.
 */
namespace alchemy\util\annotation;
/**
 * Annotation Parser
 *
 */

final class Parser
{

    final public static function parse($string)
    {
        $string = trim(preg_replace('/\r?\n *|^\/\*\*/', ' ', $string));

        preg_match_all('#' . self::MATCH_ANNOTATION . '#is', $string, $annotations);

        if (!count($annotations[1])) {
            return null;
        }
        foreach ($annotations[2] as &$value) {
            $value = self::parseValue($value);
        }
        foreach ($annotations[1] as &$name) {
            $name = strtolower($name);
        }
        $result = array_combine($annotations[1], $annotations[2]);
        return $result;
    }

    final public static function parseValue($string)
    {
        if (empty($string)) {
            return true;
        }
        //normal dock block
        if ($string[0] != '(') {
            return trim($string);
        }
        //parse annotation value

        return self::parseAnnotationValue($string);
    }


    final protected static function parseAnnotationValue($string)
    {
        $len = strlen($string) - 1;
        $result = array();
        $var = null;
        $buffer = '';
        $quota = null;
        $state = self::ST_DEFAULT;
        for ($i = 1; $i < $len; $i++) {
            $c = $string[$i];

            switch ($state) {
                case self::ST_DEFAULT:
                    if (!$buffer && $c== ' ') {
                        continue 2;
                    }

                    switch ($c) {
                        case ' ':
                            continue 3;
                        case '=':
                            $var = $buffer;
                            $buffer = '';
                            $state = self::ST_VALUE;
                            continue 3;
                        case ',':
                            if (isset(self::$keywords[$buffer])) {
                                $buffer = self::$keywords[$buffer];
                            }
                            if (empty($var)) {
                                $result[] = $buffer;
                            } else {
                                $result[$var] = $buffer;
                            }
                            $var = null;
                            $buffer = '';
                            $quota = null;
                            continue 3;
                        case '"':
                        case '\'':
                            $quota = $c;
                            $state = self::ST_STRING;
                            continue 3;
                    }
                    break;
                case self::ST_VALUE:
                    if ($c == ',') {
                        if (isset(self::$keywords[$buffer])) {
                            $buffer = self::$keywords[$buffer];
                        }
                        if (empty($var)) {
                            $result[] = $buffer;
                        } else {
                            $result[$var] = $buffer;
                        }
                        $var = null;
                        $buffer = '';
                        $quota = null;
                        $state = self::ST_DEFAULT;
                        continue 2;
                    } elseif (($c == '"' || $c == '\'') && !$buffer) {
                        $quota = $c;
                        $state = self::ST_STRING;
                        continue 2;
                    }
                    break;
                case self::ST_STRING:
                    if ($quota == $c) {
                        $state = self::ST_DEFAULT;
                        continue 2;
                    }
                    break;
            }
            $buffer .= $c;
        }
        if ($buffer) {
            if (isset(self::$keywords[$buffer])) {
                $buffer = self::$keywords[$buffer];
            }

            //there was only one parameter set in annotation so
            //do not make it an array
            if (empty($result) && !$var) {
                $result = $buffer;
            } else {
                if ($var) {
                    $result[$var] = $buffer;
                } else {
                    $result[] = $buffer;
                }
            }
        }

        return $result;
    }

    private  static $keywords = array(
        'false' => false,
        'FALSE' => false,
        'true'  => true,
        'TRUE'  => true
    );

    const ST_DEFAULT = 0;
    const ST_VALUE = 1;
    const ST_STRING = 2;
    const MATCH_ANNOTATION = '@([a-z0-9_]+)(.*?)\s+\*';
}
