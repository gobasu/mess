<?php
/**
 * Alchemy Framework (http://alchemyframework.org/)
 *
 * @link      http://github.com/dkraczkowski/alchemy for the canonical source repository
 * @copyright Copyright (c) 2012-2013 Dawid Kraczkowski
 * @license   https://raw.github.com/dkraczkowski/alchemy/master/LICENSE New BSD License
 */
namespace alchemy\storage\sql;
use alchemy\storage\Model;
use alchemy\storage\ISchema;
use alchemy\storage\IStorage;
use alchemy\storage\StorageException;

/**
 * SQL Connection class
 */
class SQL extends \PDO implements IStorage
{
    /**
     * Performes clear SQL query
     *
     * @param string $sql
     * @param ISchema $schema
     * @param array $data
     * @throws SQLException in case schema violation or other SQL problem
     * @return array if query is fetchable otherwise bool true on success false on failure
     */
    public function query($sql, array $data = null, ISchema $schema = null)
    {
        $query = $this->prepare($sql);

        if (!($query instanceof \PDOStatement) || !$query->execute($data)) {
            $error = $this->errorInfo();
            if ($error[1]) {
                throw new SQLException('[SQL ERROR] ' . $error[2] . ($schema ? ' in model class:' . $schema->getModelClass() : ''));
            }
            return;
        }

        //try to findout whatever query is fetchable or not
        $queryType = substr($sql, 0, strpos($sql, ' '));
        if (!in_array($queryType, self::$fetchableQueries)) {
            return true;
        }

        //fetch data
        $set = array();
        if ($schema) {
            while($r = $query->fetchObject($schema->getModelClass())) {
                $set[$r->getPK()] = $r;
                $r->onGet();
            }
        } else {
            $set = $query->fetchAll(\PDO::FETCH_ASSOC);
        }

        return $set;
    }

    /**
     * Saves model
     *
     * @param Model $model
     */
    public function save(Model $model)
    {
        if (!$model->isChanged()) {
            return;
        }
        if ($model->isNew()) {
            $this->insert($model);
        } else {
            $this->update($model);
        }
    }

    /**
     * Updates existing record based on model changes
     *
     * @param Model $model
     */
    protected function update(Model $model)
    {
        $schema = $model::getSchema();
        $pkField = $schema->getPKProperty()->getName();
        $where = '`' . $pkField . '` = :pk';

        $changes = $model->getChanges();

        //no changes so do not save the model
        if (empty($changes)) {
            return false;
        }

        $fields = array();
        foreach ($changes as $key => $value) {
            $fields[] = '`' . $key . '` = :' . $key;
        }

        $sql = sprintf(self::UPDATE_SQL, $schema->getCollectionName(), implode(',', $fields), $where);

        //add pk value and execute
        $changes['pk'] = $model->getPK();
        if (!$this->query($sql, $changes, $schema)) {
            $error = $this->errorInfo();
            throw new SQLException('Cannot save model: ' . $error[2]);
        }
    }

    /**
     * Inserts new record based on model data
     *
     * @param Model $model
     */
    protected function insert(Model $model)
    {

        $schema = $model::getSchema();
        $fields = array();
        $binds = array();

        $changes = $model->getChanges();


        foreach ($changes as $key => $value) {
            $fields[] = '`' . $key . '`';
            $binds[] = ':' . $key;
        }

        $sql = sprintf(self::INSERT_SQL, $schema->getCollectionName(), implode(',', $fields), implode(',', $binds));
        if(!$this->query($sql, $changes)) {
            $error = $this->errorInfo();
            throw new SQLException('Cannot save model: ' . $error[2]);
        }

        $id = $this->lastInsertId();

        if ($id) {
            $model->{$schema->getPKProperty()->getName()} = $id;
        }
    }

    /**
     * Removes record from database
     *
     * @param Model $model
     */
    public function delete(Model $model)
    {
        $schema = $model::getSchema();
        $pkField = $schema->getPKProperty();
        $where = '`' . $pkField->getName() . '` = :pk';

        $sql = sprintf(self::DELETE_SQL, $schema->getCollectionName(), $where);
        $this->query($sql, array('pk' => $model->getPK()));
    }

    public function get($model, $pkValue)
    {
        $schema = $model::getSchema();
        $fieldList = '`' . implode('`,`', $schema->getPropertyList()) . '`';
        $pkField = $schema->getPKProperty()->getName();
        $where = '`' . $pkField. '` = :pk';

        $sql = sprintf(self::GET_SQL, $fieldList, $schema->getCollectionName(), $where);

        $result = $this->query($sql, array('pk' => $pkValue), $schema);
        if (!$result) {
            return false;
        }

        $model = current($result);
        return $model;
    }

    /**
     * Finds all records matching the query in given schema
     * Qeury needs to be an array and its should represent searched value
     *
     * <code>
     * $query = array(
     *  'fieldName' => 1,
     *  'fieldName2' => 'string'
     * );
     * </code>
     * Will search for a matching records where fieldName equals 1 and fieldName2 equals 'string'
     *
     * Sort tells how the records in DB should be sorted
     *
     * <code>
     * $sort = array(
     *  'fieldName' => -1
     * );
     * </code>
     * Will sort records DESC by fieldName
     *
     * @param ISchema $schema
     * @param $query
     * @param null $sort
     * @return array
     */
    public function find(ISchema $schema, array $query = null, array $sort = null)
    {
        $sql = $this->generateFindSQL($schema, $query, $sort);
        return $this->query($sql, $query, $schema);
    }

    public function findOne(ISchema $schema, array $query = null, array $sort = null)
    {
        $sql = $this->generateFindSQL($schema, $query, $sort, 1);
        return current($this->query($sql, $query, $schema));
    }

    /**
     * Finds data matching the query and modifies it
     *
     * @param ISchema $schema
     * @param array $query query term
     * @param array $update specify update fields
     * @param bool $returnData whatever data which will be modified should be returned
     * @return array|boolean
     * @throws SQLException
     */
    public function findAndModify(ISchema $schema, array $query = null, array $update, $returnData = false)
    {
        $where = $this->parseQuery($query);
        $updateFields = array();
        $bind = array();
        foreach ($update as $field => $value) {
            switch (substr($field, 0, 1))
            {
                case '+':
                    $field = substr($field, 1);
                    $updateFields[] = '`' . $field . '` = `' . $field . '` + :' . $field;
                    break;
                case '-':
                    $field = substr($field, 1);
                    $updateFields[] = '`' . $field . '` = `' . $field . '` - :' . $field;
                    break;
                default:
                    $updateFields[] = '`' . $field . '` = :' . $field;
                    break;
            }
            if (!$schema->propertyExists($field)) {
                throw new SQLException($schema->getModelClass() . ' have not got propery `' . $field . '`');
            }
            $bind[$field] = $value;

        }

        if ($returnData) {
            $fieldList = '`' . implode('`,`', $schema->getPropertyList()) . '`';
            $sql = sprintf(self::FIND_SQL, $fieldList, $schema->getCollectionName(), $where);
            $returnData = $this->query($sql, $query, $schema);
        }

        $sql = sprintf(self::UPDATE_SQL, $schema->getCollectionName(), implode(',', $updateFields), $where);
        $bind = array_merge($query, $bind);

        //run command
        $q = $this->prepare($sql);
        if (!$q) {
            return false;
        }
        $updateQueryResult = $q->execute($bind);

        if ($updateQueryResult && $returnData !== false) {
            return $returnData;
        }
        return $updateQueryResult;
    }

    /**
     * Finds and remove rows from DB
     * @param ISchema $schema
     * @param array $query
     * @param bool $returnData
     * @return mixed returns set of models if $returnData = true or boolean otherwise
     */
    public function findAndRemove(ISchema $schema, array $query = null, $returnData = false)
    {
        $where = $this->parseQuery($query);
        if ($returnData) {
            $fieldList = '`' . implode('`,`', $schema->getPropertyList()) . '`';
            $sql = sprintf(self::FIND_SQL, $fieldList, $schema->getCollectionName(), $where);
            $returnData = $this->query($sql, $query, $schema);
        }

        $sql = sprintf(self::DELETE_SQL, $schema->getCollectionName(), $where);
        $q = $this->prepare($sql);
        if (!$q) {
            return false;
        }
        $removeQueryResult = $q->execute($query);

        if ($removeQueryResult && $returnData !== false) {
            return $returnData;
        }
        return $removeQueryResult;
    }

    private function generateFindSQL(ISchema $schema, array &$query = null, array $sort = null, $limit = null)
    {

        $fieldList = '`' . implode('`,`', $schema->getPropertyList()) . '`';

        $where = $this->parseQuery($query);
        $sql = sprintf(self::FIND_SQL, $fieldList, $schema->getCollectionName(), $where);

        if ($sort) {
            $sql .= ' ORDER BY ';
            foreach ($sort as $field => $direction) {
                if (!isset(self::$sort[$direction])) {
                    throw new SQLException(__CLASS__ . ' does not handle SORT TYPE:' . $direction);
                }
                $sql .= '`' . $field . '` ' . self::$sort[$direction] . ',';
            }
            $sql = substr($sql, 0, -1);
        }

        if ($limit) {
            $sql .= ' LIMIT '  . $limit;
        }

        return $sql;
    }

    /**
     * Parse query array to where sql
     *
     * @param array $query
     * @return string
     */
    private function parseQuery(&$query)
    {
        if (!$query)
        {
            return ' 1';
        }
        $bind = array();
        foreach ($query as $key => $value) {
            $key = trim($key);
            $operator = '=';
            $sign = substr($key, -1);
            switch ($sign) {
                case '=':
                    $sign = substr($key, -2);
                    if ($sign == '>=' || $sign == '<=') {
                        $operator = $sign;
                        $key = trim(substr($key, 0, -2));
                    } else {
                        $key = trim(substr($key, 0, -1));
                    }
                    break;
                case '>':
                case '<':
                    $operator = $sign;
                    $key = trim(substr($key, 0, -1));
                    break;
                default:
                    break;
            }

            if (is_array($value)) {
                //escape values
                foreach ($value as &$v) {
                    if (is_string($v)) {
                        $v = $this->quote($v);
                    }
                }

                $where[] = '`' . $key . '` IN (' . implode(',', $value) . ') ';
                continue;
            }

            $bind[$key] = $value;

            $where[] = '`' . $key . '` ' . $operator . ' :' . $key;
        }

        $query = $bind;
        $where = implode(' AND ', $where);
        return $where;
    }

    protected static $sort = array(
        -1  => 'DESC',
        1   => 'ASC',
        0   => ''
    );

    const INSERT_SQL    = 'INSERT INTO `%s`(%s) VALUES(%s)';
    const UPDATE_SQL    = 'UPDATE `%s` SET %s WHERE %s';
    const DELETE_SQL    = 'DELETE FROM `%s` WHERE %s';
    const GET_SQL       = 'SELECT %s FROM `%s` WHERE %s';
    const FIND_SQL      = 'SELECT %s FROM `%s` WHERE %s';

    protected static $fetchableQueries = array('EXPLAIN', 'DESCRIBE', 'SELECT', 'SHOW');
}