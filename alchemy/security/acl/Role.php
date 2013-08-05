<?php
/**
 * Alchemy Framework (http://alchemyframework.org/)
 *
 * @link      http://github.com/dkraczkowski/alchemy for the canonical source repository
 * @copyright Copyright (c) 2012-2013 Dawid Kraczkowski
 * @license   https://raw.github.com/dkraczkowski/alchemy/master/LICENSE New BSD License
 */
namespace alchemy\security\acl;

class Role
{

    public function __construct()
    {
        $this->restrictions['*'] = array();
        $this->restrictions['*']['*'] = false;
    }
    /**
     * Allows access to controller resource eg
     * user.edit allow to exec method edit on controller user
     * @param string $resource controller resource
     * @return Role
     */
    public function allow($resource)
    {
        if ($resource == '*') {
            $resource = array('*','*');
        } else {
            $resource = explode('.', $resource);
        }

        $controller = $resource[0];
        $action = isset($resource[1]) ? $resource[1] : '*';
        if (!isset($this->restrictions[$controller])) {
            $this->restrictions[$controller] = array();
            $this->restrictions[$controller]['*'] = false;
        }
        $this->restrictions[$controller][$action] = true;
        $this->restrictions[$controller]['?'] = true;

        return $this;
    }

    /**
     * @param $resource
     * @return Role
     */
    public function deny($resource)
    {
        if ($resource == '*') {
            $resource = array('*','*');
        } else {
            $resource = explode('.', $resource);
        }

        $controller = $resource[0];
        $action = isset($resource[1]) ? $resource[1] : '*';
        if (!isset($this->restrictions[$controller])) {
            $this->restrictions[$controller] = array();
            $this->restrictions[$controller]['*'] = false;
            $this->restrictions[$controller]['?'] = false;
        }
        $this->restrictions[$controller][$action] = false;

        return $this;
    }

    /**
     * @param $resource
     * @return mixed
     */
    public function hasAccess($resource)
    {
        $resource = explode('.', $resource);
        $resource[1] = isset($resource[1]) ? $resource[1] : '*';

        if (!isset($this->restrictions[$resource[0]])) {
            return $this->restrictions['*']['*'];
        }

        if (!isset($this->restrictions[$resource[0]][$resource[1]])) {
            return $this->restrictions[$resource[0]]['*'];
        }
        return $this->restrictions[$resource[0]][$resource[1]];
    }

    /**
     * Returns role restriction meta data
     * @return array restriction data
     */
    public function getRestrictionMeta()
    {
        return $this->restrictions;
    }

    private $restrictions = array();
}