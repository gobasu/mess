<?php
/**
 * Alchemy Framework (http://alchemyframework.org/)
 *
 * @link      http://github.com/dkraczkowski/alchemy for the canonical source repository
 * @copyright Copyright (c) 2012-2013 Dawid Kraczkowski
 * @license   https://raw.github.com/dkraczkowski/alchemy/master/LICENSE New BSD License
 */
namespace alchemy\future\template\html\form;
use alchemy\security\Validator as BaseValidator;
class ValidatorException extends \Exception {}
class UnknownValidatorException extends ValidatorException {}
class Validator
{
    /**
     * Creates validator object with options
     *
     * $options:
     *  - `error_msg` error message to display when data is invalid
     * more options:
     * @see alchemy\security\Validator
     *
     * @param $type
     * @param array $options
     */
    public function __construct($type, array $options = null)
    {
        if (!isset(self::$validatorList[$type])) {
            throw new UnknownValidatorException('Unknown validator type: ' . $type);
        }
        $this->options = $options;

        if (isset($options['error_msg'])) {
            $this->message = $options['error_msg'];
            unset($options['error_msg']);
        }
    }

    public function validate($input)
    {
        BaseValidator::validate($input, $this->type, $this->options);
    }

    public function setMessage($msg)
    {
        $this->message = $msg;
    }

    public function getMessage()
    {
        return $this->message;
    }

    protected $type;
    protected $message;
    protected $options;

    protected static $validatorList = array(
        BaseValidator::VALIDATE_CREDITCARD  => 1,
        BaseValidator::VALIDATE_DATE        => 1,
        BaseValidator::VALIDATE_EMAIL       => 1,
        BaseValidator::VALIDATE_IP          => 1,
        BaseValidator::VALIDATE_NUMBER      => 1,
        BaseValidator::VALIDATE_REGEXP      => 1,
        BaseValidator::VALIDATE_STRING      => 1,
        BaseValidator::VALIDATE_URL         => 1
    );
}
