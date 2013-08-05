<?php
/**
 * Alchemy Framework (http://alchemyframework.org/)
 *
 * @link      http://github.com/dkraczkowski/alchemy for the canonical source repository
 * @copyright Copyright (c) 2012-2013 Dawid Kraczkowski
 * @license   https://raw.github.com/dkraczkowski/alchemy/master/LICENSE New BSD License
 */
namespace alchemy\app\util;

/**
 * Manages multiply configs for one application
 */
class Config
{
    public function __construct($configDir = 'config')
    {
        $this->configDir = $configDir;

    }

    public function load()
    {
        $this->loaded = true;
        $this->loadCrossDomainConfig();
        $this->loadDomainConfig();
    }

    protected function loadCrossDomainConfig()
    {
        $crossDomainConfigPath = $this->configDir . '/' . Config::CROSSDOMAIN_CONFIG;
        $this->loadConfig($crossDomainConfigPath);
    }

    protected function loadDomainConfig()
    {
        if (!defined('AL_APP_HOST')) {
            define('AL_APP_HOST', isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : null);
        }
        $domainConfigPath = $this->configDir . '/' . AL_APP_HOST . '.php';
        $this->loadConfig($domainConfigPath);
    }

    private function loadConfig($file)
    {
        if (is_readable($file)) {
            $config = include_once $file;
            if (is_array($config)) {
                $this->config = array_merge($this->config , $config);
            }
        }
    }

    public function getConfig()
    {
        return $this->config;
    }

    public function isLoaded()
    {
        return $this->loaded;
    }

    public function get($name)
    {
        return isset($this->config[$name]) ? $this->config[$name] : null;
    }

    protected $loaded = false;
    protected $configDir;
    protected $config = array();
    const CROSSDOMAIN_CONFIG = '*.php';
}
