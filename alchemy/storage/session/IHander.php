<?php
/**
 * Alchemy Framework (http://alchemyframework.org/)
 *
 * @link      http://github.com/dkraczkowski/alchemy for the canonical source repository
 * @copyright Copyright (c) 2012-2013 Dawid Kraczkowski
 * @license   https://raw.github.com/dkraczkowski/alchemy/master/LICENSE New BSD License
 */
namespace alchemy\storage\session;
interface IHander
{
    /**
     * The open callback works like a constructor in classes and is
     * executed when the session is being opened. It is the first
     * callback function executed when the session is started automatically
     * or manually with session_start().
     * Should return true if success, false for failure
     *
     * @param $savePath
     * @param $sessionName
     * @return boolean
     */
    public function open($savePath, $sessionName);

    /**
     * The close callback works like a destructor in classes and is executed
     * after the session write callback has been called.
     * It is also invoked when session_write_close() is called.
     * Should return value should be true for success, false for failure.
     *
     * @return boolean
     */
    public function close();

    /**
     * The read callback must always return a session encoded (serialized) string,
     * or an empty string if there is no data to read.
     * This callback is called internally by PHP when the session starts or
     * when session_start() is called.
     * Before this callback is invoked PHP will invoke the open callback.
     *
     * @param $sessionId
     */
    public function read($sessionId);
    public function write($sessionId, $sessionData);
    public function destroy($sessionId);
    public function gc($maxLifetime);

}
