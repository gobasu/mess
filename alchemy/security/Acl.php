<?php
/**
 * Alchemy Framework (http://alchemyframework.org/)
 *
 * @link      http://github.com/dkraczkowski/alchemy for the canonical source repository
 * @copyright Copyright (c) 2012-2013 Dawid Kraczkowski
 * @license   https://raw.github.com/dkraczkowski/alchemy/master/LICENSE New BSD License
 */
namespace alchemy\security;
use alchemy\security\acl\Role;
use alchemy\storage\Session;
class AclException extends \Exception {}
class AccessDeniedException extends AclException {}
class Acl
{
    /**
     * Defines role to make it usable by addRole and removeRole
     *
     * @param $name role name
     * @return Role
     */
    public static function defineRole($name = Acl::ACL_DEFAULT)
    {
        return self::$definedRoles[$name] = new Role();
    }

    /**
     * Adds role to current user's role list
     * @param $name previously defined role name
     */
    public static function addRole($name)
    {
        if (!self::roleExists($name)) return false;
        self::$cache = array();
        self::$attachedRoles[$name] = $name;
        return true;
    }

    /**
     * Gets attached roles
     * @return array
     */
    public static function getRoles()
    {
        return self::$attachedRoles;
    }

    /**
     * Removes role from current user's role list
     * @param $name previously defined role name
     */
    public static function removeRole($name)
    {
        if (self::hasRole($name))
        {
            self::$cache = array();
            unset(self::$attachedRoles[$name]);
        }
    }

    /**
     * Checks if user has given role in their role list
     *
     * @param $name previously defined role name
     * @return bool true if user has the role otherwise false
     */
    public static function hasRole($name)
    {
        return isset(self::$attachedRoles[$name]);
    }

    /**
     * Checks whatever role was defined or not
     *
     * @param $name role name
     * @return bool true if role is defined otherwise false
     */
    public static function roleExists($name)
    {
        return isset(self::$definedRoles[$name]);
    }

    /**
     * Checks whatever user has access to passed resource
     * @return bool
     */
    public static function isAllowed($resource)
    {
        //if (key_exists($key, self::$cache)) return self::$cache[$key];

        $access = false;

        foreach (self::$attachedRoles as $role)
        {
            $role = self::$definedRoles[$role];
            if (!$role->hasAccess($resource)) {
                continue;
            }
            $access = true;
            break;
        }

        self::$cache[$resource] = $access;
        return $access;
    }

    /**
     * Remove all attached roles except default one
     */
    public static function forget()
    {
        self::$attachedRoles = array();
        self::$cache = array();
        self::addRole(self::ACL_DEFAULT);
    }

    public static function setup()
    {
        /**
         * define default role and deny for all,
         * can be overriten by Acl::defineRole()->...
         */
        self::defineRole()->deny('*');
        $acl = Session::get('acl');
        self::$attachedRoles = &$acl['user_roles'];
        self::$cache = &$acl['cache'];

        if (!count(self::$attachedRoles))
        {
            self::$attachedRoles = array();
            self::addRole(self::ACL_DEFAULT);
        }

        if (!self::$cache) self::$cache = array();
    }

    const ACL_DEFAULT = 'DefaultRole';

    private static $attachedRoles = array();
    private static $definedRoles = array();
    private static $cache = array();
}

/**
 * @side-efect
 * When loaded Acl is automatically setup
 */
Acl::setup();