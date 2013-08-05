<?php
/**
 * Alchemy Framework (http://alchemyframework.org/)
 *
 * @link      http://github.com/dkraczkowski/alchemy for the canonical source repository
 * @copyright Copyright (c) 2012-2013 Dawid Kraczkowski
 * @license   https://raw.github.com/dkraczkowski/alchemy/master/LICENSE New BSD License
 */
namespace alchemy\storage;

interface ISchema
{
    /**
     * @return Property
     */
    public function getPKProperty();

    /**
     * @return array
     */
    public function getPropertyList();

    /**
     * @return string
     */
    public function getStorageClass();

    /**
     * @param string $name
     * @return Property
     */
    public function getProperty($name);

    /**
     * @return string
     */
    public function getCollectionName();

    /**
     * @param string $name
     * @return bool
     */
    public function propertyExists($name);

    /**
     * @return string
     */
    public function getModelClass();
}
