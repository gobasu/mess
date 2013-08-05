<?php
/**
 * Alchemy Framework (http://alchemyframework.org/)
 *
 * @link      http://github.com/dkraczkowski/alchemy for the canonical source repository
 * @copyright Copyright (c) 2012-2013 Dawid Kraczkowski
 * @license   https://raw.github.com/dkraczkowski/alchemy/master/LICENSE New BSD License
 */
namespace alchemy\storage;
use alchemy\storage\session\SessionNamespace;
class Session
{
    /**
     * Starts the session
     */
    public static function start()
    {
        if (self::isActive()) {
            return false;
        }
        if (self::$handler) {
            session_set_save_handler(
                array(self::$handler, 'open'),
                array(self::$handler, 'close'),
                array(self::$handler, 'read'),
                array(self::$handler, 'write'),
                array(self::$handler, 'destroy'),
                array(self::$handler, 'gc')
            );
        }
        session_start();
        self::$data = &$_SESSION;
        self::$sessionId = session_id();
        return true;
    }

    /**
     * Checks if session is started
     *
     * @return bool
     */
    public static function isActive()
    {
        if (self::$sessionId) {
            return true;
        }
        return false;
    }

    /**
     * Gets session id
     * @return string
     */
    public static function getID()
    {
        return session_id();
    }

    /**
     * Sets session id
     * @param $id
     */
    public static function setID($id)
    {
        self::$sessionId = $id;
        session_id($id);
    }

    /**
     * Sets session handler
     *
     * @param session\IHander $handler
     */
    public static function setHandler(\alchemy\storage\session\IHander $handler)
    {
        self::$handler = $handler;
    }

    /**
     * Destroys the session
     */
    public static function destroy()
    {
        session_destroy();
    }

    /**
     *
     * @param $name
     * @return SessionNamespace
     */
    public static function &get($name)
    {
        if (!isset(self::$data[$name])) {
            self::$data[$name] = new SessionNamespace();
        }
        return self::$data[$name];
    }

    private static $data = array();

    /**
     * @var \alchemy\storage\session\IHander
     */
    private static $handler;

    /**
     * @var string
     */
    protected static $sessionId;
}