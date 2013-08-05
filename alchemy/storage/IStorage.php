<?php
/**
 * Alchemy Framework (http://alchemyframework.org/)
 *
 * @link      http://github.com/dkraczkowski/alchemy for the canonical source repository
 * @copyright Copyright (c) 2012-2013 Dawid Kraczkowski
 * @license   https://raw.github.com/dkraczkowski/alchemy/master/LICENSE New BSD License
 */
namespace alchemy\storage;

/**
 * IStorage
 *
 * @author: lunereaper
 */
interface IStorage
{
    public function save(Model $model);
    public function delete(Model $model);
    public function get($modelName, $parameters);
    public function find(ISchema $schema, array $query = null, array $sort = null);
    public function findOne(ISchema $schema, array $query = null, array $sort = null);
    public function findAndModify(ISchema $schema, array $query = null, array $update, $returnData = false);
    public function findAndRemove(ISchema $schema, array $query = null, $returnData = false);
}
