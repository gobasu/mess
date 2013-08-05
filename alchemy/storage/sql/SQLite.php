<?php
/**
 * Alchemy Framework (http://alchemyframework.org/)
 *
 * @link      http://github.com/dkraczkowski/alchemy for the canonical source repository
 * @copyright Copyright (c) 2012-2013 Dawid Kraczkowski
 * @license   https://raw.github.com/dkraczkowski/alchemy/master/LICENSE New BSD License
 */
namespace alchemy\storage\sql;

class SQLiteException extends SQLException {}

/**
 * SQLite Connection class
 */

class SQLite extends SQL
{

    public function __construct($fileName = self::USE_MEMORY)
    {
        $dsn = 'sqlite:' . $fileName;
        parent::__construct($dsn);
    }

    CONST USE_MEMORY = ':memory:';
}