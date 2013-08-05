<?php
/**
 * Alchemy Framework (http://alchemyframework.org/)
 *
 * @link      http://github.com/dkraczkowski/alchemy for the canonical source repository
 * @copyright Copyright (c) 2012-2013 Dawid Kraczkowski
 * @license   https://raw.github.com/dkraczkowski/alchemy/master/LICENSE New BSD License
 */
namespace alchemy\template;
use alchemy\template\mixture\Tokenizer;
use alchemy\template\mixture\Parser;
use alchemy\template\mixture\Compiler;
use alchemy\template\mixture\Template;

class MixtureException extends \Exception {}
/**
 * Mixture templating engine is mixin of
 * mustashe and jinja templating systems
 *
 * It gathers the best parts of both simplifies
 * and compound them into one not too much logic tpl
 */
class Mixture
{
    /**
     * Sets how date values should be formatted in template
     *
     * @param string $format
     */
    public static function setDateFormat($format = 'Y.m.d')
    {
        Template::setOption(Template::OPTION_DATE_FORMAT, $format);
    }

    /**
     * Sets how datetime values in template should be formatted
     * @param string $format
     */
    public static function setDatetimeFormat($format = 'Y.m.d H:i:s')
    {
        Template::setOption(Template::OPTION_DATETIME_FORMAT, $format);
    }

    /**
     * Sets how float numbers and currency values should be displayed
     *
     * @param int $decimals
     * @param string $decimalsSeparator
     * @param string $thousandsSeparator
     */
    public static function setNumberFormat($decimals = 0, $decimalsSeparator = '.', $thousandsSeparator = ',')
    {
        Template::setOption(Template::OPTION_NUMBER_FORMAT, array($decimals, $decimalsSeparator, $thousandsSeparator));
    }

    public static function setCurrencySuffix($suffix = 'USD')
    {
        Template::setOption(Template::OPTION_CURRENCY_SUFFIX, $suffix);
    }

    public function __construct($dir = null)
    {
        if (!$dir) {
            $this->dir = AL_APP_DIR . DIRECTORY_SEPARATOR . 'template';
        } else {
            $this->dir = realpath($dir);
        }
        $this->cache = sys_get_temp_dir();
    }

    public static function addHelper($name, $callable)
    {
        self::$helpers[$name] = $callable;
    }

    public static function helperExists($name)
    {
        return isset(self::$helpers[$name]);
    }

    public static function callHelper($name, $args)
    {
        if (!self::helperExists($name)) {
            throw new MixtureException('Call to undefined helper function ' . $name);
        }
        return call_user_func_array(self::$helpers[$name], $args);
    }

    public function setCacheDir($dir)
    {
        $dir = realpath($dir);
        if (!is_dir($dir)) {
            throw new MixtureException('Cache dir ' . $dir . ' does not exists');
        }
        $this->cache = $dir;
    }

    public function disableCache()
    {
        $this->cache = false;
    }

    public function render($name, &$data = array())
    {
        if ($this->cache) {
            Template::setCacheDir($this->cache);
        } else {
            Template::setCacheDir(false);
        }
        Template::setTemplateDir($this->dir);
        $tpl = Template::factory($name, $data);
        return $tpl->render();

    }

    protected $dir;
    protected $cache;

    protected static $helpers;

}
