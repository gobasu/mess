<?php
/**
 * Alchemy Framework (http://alchemyframework.org/)
 *
 * @link      http://github.com/dkraczkowski/alchemy for the canonical source repository
 * @copyright Copyright (c) 2012-2013 Dawid Kraczkowski
 * @license   https://raw.github.com/dkraczkowski/alchemy/master/LICENSE New BSD License
 */
namespace alchemy\storage;
use alchemy\storage\Storage;
use alchemy\app\Loader;
use alchemy\event\EventDispatcher;
use alchemy\storage\ISchema;

class ModelException extends \Exception {}
/**
 * Entity
 *
 * Is standalone representation of record in database
 * The framework handles 6 datatypes:
 * -bool
 * -number
 * -string
 * -blob
 * -date
 * -enum
 *
 * When defining a property in entity you should use 'Param' annotation
 * to tell what behavior should be applied to given parameter, for example
 * \@Param(type=bool)
 * Additionaly you can dynamically map class property name into your db's
 * property name by using attribute name, example
 * \@Param(type=number,name=my_db_column)
 *
 *
 * @author: lunereaper
 */
abstract class Model extends EventDispatcher
{
    /**
     * Construct new Model
     *
     * @param string|int|array $data pk value or data
     */
    public function __construct($data = null)
    {
        $pkName = self::getSchema()->getPKProperty()->getName();
        if ($data === null) {
            return;
        } elseif (is_string($data) || is_numeric($data)) {
            $this->{$pkName} = $data;
        } elseif (is_array($data)) {
            //if PK is set
            if (isset($data[$pkName])) {
                $this->{$pkName} = $data[$pkName];
            }
            $this->set($data);
        } else {
            throw new ModelException('Model::__construct() accepts string|int|array, ' . gettype($data) . ' passed');
        }
    }

    /**
     * Creates or fetch model in the way it stays unchanged
     *
     * @param array $data
     * @param Model $model
     * @return Model
     */
    public static function create(array $data, Model $model = null)
    {
        if (!$model) {
            $class = get_called_class();
            $model = new $class();
        }

        foreach ($data as $property => $value) {
            $model->{$property} = $value;
        }

        return $model;
    }

    public static function onLoad()
    {

    }


    /**
     * Sets multiple parameters in model
     *
     * @param array $data
     */
    public function set(array $data, $force = false)
    {
        foreach ($data as $property => $value) {
            $this->__set($property, $value);
        }
    }

    /**
     * Gets schema object corresponding to given model class
     * If schema was not loaded than generates a new one
     *
     * @return ISchema
     */
    public static function getSchema()
    {
        $class = get_called_class();
        if (isset(self::$schemaList[$class])) {
            return self::$schemaList[$class];
        }
        return self::$schemaList[$class] = SchemaBuilder::getSchema($class);
    }

    /**
     * Gets data corresponding to given PK from current connection
     *
     * @param $pk
     * @return Model
     */
    public static function get($pk)
    {
        $modelName = get_called_class();
        $storage = self::getStorage();
        return $storage->get($modelName, $pk);
    }

    /**
     * Finds first object which matches given query
     *
     * @param $query
     * @param array $sort
     * @return Model
     */
    public static function findOne(array $query = array(), array $sort = null)
    {
        $storage = self::getStorage();
        return $storage->findOne(self::getSchema(), $query, $sort);

    }

    /**
     * Finds all objects in DB which match given query
     *
     * @param array $query
     * @param array $sort sorts objects by given field
     * @see More in coresponding to model IConnection handler
     * @return array
     */
    public static function find(array $query = array(), array $sort = null)
    {
        $storage = self::getStorage();
        return $storage->find(self::getSchema(), $query, $sort);
    }

    public static function findAndRemove($query, $returnData = false)
    {
        $storage = self::getStorage();
        return $storage->findAndRemove(self::getSchema(), $query, $returnData);
    }

    public static function findAndModify(array $query = null, array $update, $returnData = false)
    {
        $storage = self::getStorage();
        return $storage->findAndModify(self::getSchema(), $query, $update, $returnData);
    }

    /**
     * Provides interface for custom queries for details
     * look into used connection class
     */
    public static function query(/** mutable **/)
    {
        $storage = self::getStorage();
        if (!method_exists($storage, 'query')) {
            throw new ModelException(get_class($storage) . ' does not support custom queries');
        }
        return call_user_func_array(array($storage, 'query'), func_get_args());
    }

    /**
     * Calculates the changes and writes them to $this->changes array
     *
     * @param string $name
     * @param mixed $value
     * @throws ModelException
     */
    public function __set($name, $value)
    {
        if (!self::getSchema()->propertyExists($name)) {
            throw new ModelException('Trying to set non existing property `' . $name . '` in model ' . get_called_class());
        }

        //add to set fields
        $this->setFields[$name] = $name;

        if (!isset($this->changes[$name])) {
            if ($this->{$name} != $value) {
                $this->changes[$name] = $value;
                $this->isChanged = true;
            }
            return;
        }

        if ($this->changes[$name] != $value) {

            //value was changed to existing in storage
            if ($value == $this->{$name}) {
                unset ($this->changes[$name]);
                $this->isChanged = false;
                return;
            }

            $this->changes[$name] = $value;
            $this->isChanged = true;
        }

    }

    /**
     * Gets model's property
     *
     * @param $name
     * @return mixed
     */
    public function __get($name)
    {
        if (isset($this->changes[$name])) {
            return $this->changes[$name];
        }

        return $this->{$name};
    }

    /**
     * Override this method if you need
     * Called everytime when framework is trying to put model
     * to the database
     */
    public function onSave()
    {}

    /**
     * Override this method if you need
     * Called everytime when model have been saved to database
     */
    public function onPersists()
    {}

    /**
     * Override this method if you need
     * Called everytime when model was purged from db
     */
    public function onDelete()
    {}

    /**
     * Called everytime when model's property changes
     */
    public function onChange()
    {}

    /**
     * Override this method if you need
     * Called when record was fetched from DB
     */
    public function onGet()
    {}

    public function isNew()
    {
        return $this->getPK() == null ? true : false;
    }

    public function isChanged()
    {
        return $this->isChanged;
    }

    /**
     * Gets model's PK value
     *
     * @return mixed
     */
    public function getPK()
    {
        return $this->{self::getSchema()->getPKProperty()->getName()};
    }

    /**
     * Set model's PK value
     *
     * @param $value
     */
    private function setPK($value)
    {
        $this->{self::getSchema()->getPKProperty()->getName()} = $value;
    }

    /**
     * Persists model to database
     *
     * @return bool
     */
    public function save()
    {
        $storage = self::getStorage();
        $this->onSave();
        $storage->save($this);
        $this->applyChanges();
        $this->onPersists();
        return true;

    }

    /**
     * Purges model from database
     */
    public function delete()
    {
        $storage = self::getStorage();
        $this->onDelete();
        $storage->delete($this);

        //set model as fresh one after deletion
        $this->isChanged = true;
        $this->changes = $this->serialize();
        $this->setPK(null);
    }

    /**
     * Forces the model persisting even if no changes were applied to model
     */
    public function forceSave()
    {
        $this->forceSave = true;
        $this->isChanged = true;
    }

    /**
     * Returns serialized model to an assoc. array (key -> value)
     * @return array
     */
    public function serialize()
    {
        $schema = self::getSchema();
        $serialized = array();
        foreach ($schema as $name => $property) {
            if (isset($this->changes[$name])) {
                $serialized[$name] = $this->changes[$name];
            } else {
                $serialized[$name] = $this->{$name};
            }
        }
        return $serialized;
    }

    public function getChanges()
    {
        if ($this->forceSave) {
            $changes = array();
            foreach ($this->setFields as $field) {
                $changes[$field] = $this->__get($field);
            }
            return $changes;
        }
        return $this->changes;
    }

    /**
     * Dispatches an event to EventHub
     *
     * @param \alchemy\event\Event $e
     */
    public function dispatch(\alchemy\event\Event $e)
    {
        \alchemy\event\EventHub::dispatch($e);
        parent::dispatch($e);
    }

    /**
     * Gets model's connection
     *
     * @return IStorage|\PDO
     */
    protected static function getStorage()
    {
        $schema = self::getSchema();
        return Storage::get($schema->getStorageClass());
    }

    private function applyChanges()
    {
        foreach ($this->changes as $key => $value) {
            $this->{$key} = $value;
        }
        $this->changes = array();
        $this->isChanged = false;
    }

    protected $forceSave = false;

    protected $changes = array();

    protected $setFields = array();

    protected $isChanged = false;

    /**
     * Model's schema
     *
     * @var array of \alchemy\db\ISchema
     */
    protected static $schemaList = array();

}
