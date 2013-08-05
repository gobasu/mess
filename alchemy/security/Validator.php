<?php
/**
 * Alchemy Framework (http://alchemyframework.org/)
 *
 * @link      http://github.com/dkraczkowski/alchemy for the canonical source repository
 * @copyright Copyright (c) 2012-2013 Dawid Kraczkowski
 * @license   https://raw.github.com/dkraczkowski/alchemy/master/LICENSE New BSD License
 */
namespace alchemy\security;

class Validator
{
    /**
     *
     * @param string $type
     * @param array $options
     *
     * Number validation options
     * - min_value (int)
     *      default: null
     * - max_value (int)
     *      default: null
     *
     * String validation options
     * - min_length (int)
     *      default: null
     * - max_length (int)
     *      default: null
     *
     * Regex validation options
     * - *regex (string)
     *      default null
     *
     * Date validation options
     * - format (string)
     *      default null
     * - timestamp
     *      default false
     * - min_range
     *      default null
     * - max_range
     *      default null
     * List validation
     *
     * $options should be an list array
     *
     */
    public static function validate($input, $validator, array $options = null)
    {
        switch ($validator)
        {
            case self::VALIDATE_DATE:
            {
                return self::date($input, $options);
            }
            case self::VALIDATE_NUMBER:
            {
                return self::number($input, $options);
            }
            case self::VALIDATE_STRING:
            {
                return self::string($input, $options);
            }
            case self::VALIDATE_EMAIL:
            {
                return self::email($input);
            }
            case self::VALIDATE_IP:
            {
                return self::ip($input);
            }
            case self::VALIDATE_URL:
            {
                return self::url($input);
            }
            case self::VALIDATE_REGEXP:
            {
                return self::regexp($input, $options['regex']);
            }
            case self::VALIDATE_CREDITCARD:
            {
                return self::creditCard($input, $options['type']);
            }
        }
    }

    /**
     * Validates if given number is valid credit card number
     * $type can be:
     *  -visa
     *  -amex
     *  -jcb
     *  -maestro
     *  -solo
     *  -mastercard
     *  -switch
     *
     * @param int|string $number
     * @param null|string $type credit card type
     * @return bool
     */
    public static function creditCard($number, $type = null)
    {
        $types = array(
            'visa' => '(4\d{12}(?:\d{3})?)',
            'amex' => '(3[47]\d{13})',
            'jcb' => '(35[2-8][89]\d\d\d{10})',
            'maestro' => '((?:5020|5038|6304|6579|6761)\d{12}(?:\d\d)?)',
            'solo' => '((?:6334|6767)\d{12}(?:\d\d)?\d?)',
            'mastercard' => '(5[1-5]\d{14})',
            'switch' => '(?:(?:(?:4903|4905|4911|4936|6333|6759)\d{12})|(?:(?:564182|633110)\d{10})(\d\d)?\d?)',
        );

        if ($type && isset($types[$type])) {
            if (!preg_match('#^' . $types[$type] . '$#', $number)) {
                return false;
            }
        } else {
            foreach ($types as $t => $match) {
                if (preg_match('#^' . $match . '$#', $number)) {
                    return self::luhn($number);
                }
            }
            return false;
        }

        return self::luhn($number);
    }

    /**
     * Validates number if it is mod10 compatible
     * @param $number
     * @see https://gist.github.com/1287893
     */
    public static function luhn($number)
    {
        $number = (string) $number;
        $sumTable = array(
            array(0,1,2,3,4,5,6,7,8,9),
            array(0,2,4,6,8,1,3,5,7,9)
        );
        $sum = 0;
        $flip = 0;
        for ($i = strlen($number) - 1; $i >= 0; $i--) {
            $sum += $sumTable[$flip++ & 0x1][$number[$i]];
        }
        return $sum % 10 === 0;
    }

    /**
     * Validates the number
     * Number validation options
     * - min_value (int)
     *      default: null
     * - max_value (int)
     *      default: null
     *
     * @param float|int $value
     * @param array $options
     * @return bool
     */
    public static function number($value, $options = array())
    {
        if (!is_numeric($value)) return false;

        if (isset($options['min_value']) && $value < $options['min_value']) return false;
        if (isset($options['max_value']) && $value > $options['max_value']) return false;

        return true;
    }

    /**
     * Validates the string
     *
     * String validation options
     * - min_length (int)
     *      default: null
     * - max_length (int)
     *      default: null
     *
     * @param string $value
     * @param array $options
     * @return bool
     */
    public static function string($value, $options = array())
    {
        if (!is_string($value)) return false;

        if (!isset($options)) return true;
        $length = strlen($value);

        if (isset($options['min_length']) && $length < $options['min_length']) return false;
        if (isset($options['max_length']) && $length > $options['max_length']) return false;

        return true;

    }

    /**
     * Validates if given value is proper email address
     *
     * @param string $value
     * @return bool
     */
    public static function email($value)
    {
        return filter_var($value, FILTER_VALIDATE_EMAIL) ? true : false;
    }

    /**
     * Validates if given value is proper url address
     *
     * @param string $value
     * @return bool
     */
    public static function url($value)
    {
        return filter_var($value, FILTER_VALIDATE_URL) ? true : false;
    }

    /**
     * Validates if given value is proper ip address
     *
     * @param string $value
     * @return bool
     */
    public static function ip($value)
    {
        return filter_var($value, FILTER_VALIDATE_IP) ? true : false;
    }

    /**
     * Validate if given value match the regular expression
     *
     * @param string $value
     * @param string $regexp
     * @return bool
     */
    public static function regexp($value, $regexp)
    {

        return preg_match('#' . $regexp . '#', $value) ? true : false;
    }

    /**
     * Validates date
     *
     * Date validation options
     * - format (string)
     *      default null
     * - timestamp
     *      default false
     * - min_range
     *      default null
     * - max_range
     *      default null
     *
     * @param string $value
     * @param array $options
     * @return bool
     */
    public static function date($value, $options = array())
    {
        $timestamp = strtotime($value);

        if (!is_numeric($timestamp)) {
            return false;
        }

        if (!checkdate(date('m', $timestamp), date('d', $timestamp), date('Y', $timestamp))) {
            return false;
        }

        if (isset($options['format'])) {
            $format = $options['format'];
            $format = str_replace(array('\\', '-','/',':', '[' , ']' , '(',')'), array('\\\\' ,'\-','\/','\:','\[','\]','\(','\)'), $format);

            $regex = str_replace(
                array('Y','m', 'M', 'd', 'j' , 'y', 'H', 'h' , 'a', 'A', 'g', 'G', 'i', 's'),
                array('[0-9]{4}','[0-9]{2}', '\w{3}', '[0-9]{2}', '[0-9]{1,2}', '[0-9]{2}', '[0-9]{2}', '[0-9]{2}', '\w{2}', '\w{2}', '[0-9]{1,2}', '[0-9]{1,2}', '[0-9]{2}', '[0-9]{2}'),
                $format
            );
            if (!preg_match('#' . $regex . '#', $value)) {
                return false;
            }
        }

        if (isset($options['min_range']) && strtotime($options['min_range']) > $timestamp) {
            return false;
        }

        if (isset($options['max_range']) && strtotime($options['max_range']) < $timestamp) {
            return false;
        }

        return true;

    }

    const VALIDATE_NUMBER   = 'number';
    const VALIDATE_STRING   = 'string';
    const VALIDATE_EMAIL    = 'email';
    const VALIDATE_REGEXP   = 'regex';
    const VALIDATE_IP       = 'ip';
    const VALIDATE_DATE     = 'date';
    const VALIDATE_URL      = 'url';
    const VALIDATE_CREDITCARD='creditCard';

    const CC_TYPE_VISA      = 'visa';
    const CC_TYPE_JBC       = 'jbc';
    const CC_TYPE_MAESTRO   = 'maestro';
    const CC_TYPE_SOLO      = 'solo';
    const CC_TYPE_MASTERCARD= 'mastercard';
    const CC_TYPE_SWITCH    = 'switch';
}