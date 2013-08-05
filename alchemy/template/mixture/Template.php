<?php
/**
 * Alchemy Framework (http://alchemyframework.org/)
 *
 * @link      http://github.com/dkraczkowski/alchemy for the canonical source repository
 * @copyright Copyright (c) 2012-2013 Dawid Kraczkowski
 * @license   https://raw.github.com/dkraczkowski/alchemy/master/LICENSE New BSD License
 */
namespace alchemy\template\mixture;
use alchemy\template\MixtureException;

class TemplateException extends MixtureException {}

class Template
{
    /**
     * @param $name
     * @return Template
     */
    public static function factory($name, &$data)
    {
        self::load($name);
        $templateFileName = self::getTemplateFileName($name);
        $templateClassName = Compiler::getTemplateClassName($templateFileName);
        return new $templateClassName($data);
    }

    /**
     * Returns template filename
     * @param string $name
     * @return string
     */
    public static function getTemplateFileName($name)
    {
        return self::$templateDir . DIRECTORY_SEPARATOR . $name;
    }

    /**
     * Loads template classes
     *
     * @param $name template name
     */
    public static function load($name)
    {
        $templateFileName = self::getTemplateFileName($name);

        if (!is_readable($templateFileName)) {
            throw new TemplateException('Template file:' . $templateFileName . ' is not readable');
        }
        $templateClassName = Compiler::getTemplateClassName($templateFileName);

        //template was already loaded
        if (class_exists($templateClassName)) {
            return true;
        }

        $templateCacheFileName = DIRECTORY_SEPARATOR . $templateClassName . '.php';

        //template from cache if cache was not disabled
        if (self::$cacheDir) {
            $templateCacheFileName = self::$cacheDir . $templateCacheFileName;
            if (is_readable($templateCacheFileName) && filemtime($templateCacheFileName) >= filemtime($templateFileName)) {
                require_once $templateCacheFileName;
                return true;
            }
        } else {
            $templateCacheFileName = sys_get_temp_dir() . $templateCacheFileName;
        }

        //parse template
        try {
            $parser = new Parser(new Tokenizer($templateFileName));
        } catch (\Exception $e) {
            throw new TemplateException('Unexpected error occured while loading your template file');
        }
        $compiler = new Compiler($templateFileName);
        $compiler->compile($parser->parse());

        $template = $compiler->getOutput($templateClassName);
        //save cache & return new template object
        file_put_contents($templateCacheFileName, $template);
        require_once $templateCacheFileName;

        return true;
    }

    public static function setOption($name, $value)
    {
        self::$options[$name] = $value;
    }

    public static function getOption($name)
    {
        if (!isset(self::$options[$name])) {
            return null;
        }
        return self::$options[$name];
    }

    /**
     * Sets new cache dir, default is sys_get_temp()
     *
     * @param $dir
     */
    public static function setCacheDir($dir)
    {
        self::$cacheDir = $dir;
    }

    /**
     * Sets dirpath were mixture will be searching fro tempaltes
     *
     * @param $dir
     */
    public static function setTemplateDir($dir)
    {
        self::$templateDir = $dir;
    }

    protected function __construct(&$data = array())
    {
        if ($data instanceof VarStack) {
            $this->stack = $data;
        } else {
            $this->stack = new VarStack($data);
        }
    }

    /**
     * Imports other template
     *
     * @param string $name template name
     */
    public function import($name)
    {
        echo self::factory($name, $this->stack)->render();
    }

    /**
     * Renders the template
     *
     * @return string
     */
    public function render()
    {
        return '';
    }

    /**
     * @var string
     */
    protected static $cacheDir;

    /**
     * @var array
     */
    protected static $options = array(
        self::OPTION_DATE_FORMAT       => 'Y.m.d',
        self::OPTION_DATETIME_FORMAT   => 'Y.m.d H:i:s',
        self::OPTION_TIME_FORMAT       => 'H:i:s',
        self::OPTION_NUMBER_FORMAT     => array(0, '.' , ','),
        self::OPTION_CURRENCY_SUFFIX   => 'USD'
    );

    /**
     * @var string
     */
    protected static $templateDir;

    /**
     * @var VarStack
     */
    protected $stack;

    const OPTION_DATE_FORMAT       = 1;
    const OPTION_DATETIME_FORMAT   = 2;
    const OPTION_TIME_FORMAT       = 3;
    const OPTION_NUMBER_FORMAT     = 4;
    const OPTION_CURRENCY_SUFFIX   = 5;
}
