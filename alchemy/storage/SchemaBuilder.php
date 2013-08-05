<?php
/**
 * Alchemy Framework (http://alchemyframework.org/)
 *
 * @link      http://github.com/dkraczkowski/alchemy for the canonical source repository
 * @copyright Copyright (c) 2012-2013 Dawid Kraczkowski
 * @license   https://raw.github.com/dkraczkowski/alchemy/master/LICENSE New BSD License
 */
namespace alchemy\storage;

use alchemy\storage\ModelException;
use alchemy\storage\ISchema;
use alchemy\util\AnnotationReflection;
use alchemy\app\Loader;

class SchemaBuilderException extends \Exception {}
/**
 * SchemaBuilder
 */
class SchemaBuilder
{
    /**
     * Gets Schema for given Entity classname
     * If Schema Class is valid will load cached class
     * otherwise new Schema will be generated and instance
     * of this class will be returned
     *
     * @param string $className
     * @return ISchema
     */
    public static function getSchema($className)
    {
        $schemaBuilder = new SchemaBuilder($className);
        $path = Loader::getPathForApplicationClass($className);
        $schemaPath = AL_APP_CACHE_DIR . '/' . sha1($path) . '.php';

        if (is_readable($schemaPath) && self::$cache) {
            if (filemtime($schemaPath) >= filemtime($path)) {
                //get the schema from cache
                require_once $schemaPath;
                return $schemaBuilder->getInstance();
            }
        }

        //build schema
        $schemaBuilder->build();
        $schemaBuilder->save($schemaPath);
        require_once $schemaPath;
        return $schemaBuilder->getInstance();
    }

    public static function disableCache()
    {
        self::$cache = false;
    }

    protected function __construct($className)
    {
        $this->className = $className;
        $this->schemaClassName = $className . self::SCHEMA_CLASS_POSTFIX;
    }

    protected function build()
    {
        $annotationReflection = new AnnotationReflection($this->className);

        $classAnnotations = $annotationReflection->getFromClass();
        $propertyList = $annotationReflection->getDeclaredProperties();
        $storageClass = Storage::getDefaultStorage();

        //get PK for Entity
        if (!isset($classAnnotations[self::ANNOTATION_PK])) {
            throw new ModelException('Missing @' . self::ANNOTATION_PK . ' annotation in ' . $this->className . ' definition');
        }
        $pk = $classAnnotations[self::ANNOTATION_PK];

        if (is_array($pk)) {
            throw new ModelException('Alchemy models does not support compound keys yet!');
        }

        if (isset($classAnnotations[self::ANNOTATION_STORAGE_CLASS])) {
            $storageClass = $classAnnotations[self::ANNOTATION_STORAGE_CLASS];
            echo 'storageClass:' . $storageClass;
        }
        $className = explode('\\', $this->className);
        $namespace = implode('\\', array_slice($className,0, -1));
        if ($namespace) {
            $namespace = 'namespace ' . $namespace . ';';
        }
        $className = array_slice($className, -1);

        //get Collection name or use class name
        if (isset($classAnnotations[self::ANNOTATION_COLLECTION])) {
            $collectionName = $classAnnotations[self::ANNOTATION_COLLECTION];
        } else {
            $collectionName = $className[0];
        }

        $className = $className[0] . self::SCHEMA_CLASS_POSTFIX;
        $constructBody = '';
        $propertyAliases = array();

        foreach ($propertyList as $propertyName) {

            $propertyAnnotation = $annotationReflection->getFromProperty($propertyName);

            //ommit properties without @Param annotation
            if (!isset($propertyAnnotation[self::ANNOTATION_PROPERTY])) {
                continue;
            }
            $annotation = $propertyAnnotation[self::ANNOTATION_PROPERTY];

            //add property to schema
            $property = '        $this->propertyList[\'' . $propertyName . '\']';

            $constructBody .= PHP_EOL . $property . ' = new \alchemy\storage\Property(\'' . $propertyName . '\');';

            if (isset($annotation[self::PROPERTY_ATTRIBUTE_NAME])) {
                $propertyAliases[] = "\n\t" . '\'' . $propertyName . '\' => \'' . $annotation[self::PROPERTY_ATTRIBUTE_NAME] . '\'';
            } else {
                $propertyAliases[] = "\n\t" . '\'' . $propertyName . '\' => \'' . $propertyName . '\'';
            }

            //set property type
            if (!isset($annotation[self::PROPERTY_ATTRIBUTE_TYPE])) {
                $type = self::$typeMap['string'];
            } else {
                $type = isset(self::$typeMap[$annotation[self::PROPERTY_ATTRIBUTE_TYPE]]) ? self::$typeMap[$annotation[self::PROPERTY_ATTRIBUTE_TYPE]] : self::$typeMap['default'];
            }
            $constructBody .= PHP_EOL . $property . '->setType(' . $type . ');';

            //is property required
            if (isset($annotation[self::PROPERTY_ATTRIBUTE_REQUIRED])) {
                $constructBody .= PHP_EOL . $property . '->setRequired();';
            }
        }

        $this->schemaData = sprintf(self::CLASS_TEMPLATE,
            $namespace, //Schema namespace
            $className, //Schame class name
            $constructBody, //set properties
            $pk, //set pk field name
            $storageClass, //set storage class name
            implode(',', $propertyAliases), //set property fields name
            $collectionName, // set collection name
            $this->className // set model class name
        );
    }

    protected function getInstance()
    {
        return new $this->schemaClassName;
    }

    protected function save($filename)
    {
        if ((is_file($filename) && !is_writable($filename)) || !is_writable(dirname($filename)))  {
            throw new SchemaBuilderException('File ' . $filename . ' is not writeable');
        }
        file_put_contents($filename,'<?php' . PHP_EOL . $this->schemaData);
    }

    /**
     * @var string
     */
    protected $className;
    protected $schemaClassName;
    protected $schemaData;

    protected static $cache = true;
    protected static $typeMap = array(
        'string'    => Property::TYPE_STRING,
        'text'      => Property::TYPE_STRING,
        'int'       => Property::TYPE_NUMBER,
        'float'     => Property::TYPE_NUMBER,
        'number'    => Property::TYPE_NUMBER,
        'date'      => Property::TYPE_DATE,
        'json'      => Property::TYPE_JSON,
        'enum'      => Property::TYPE_ENUM,
        'bool'      => Property::TYPE_BOOL,
        'boolean'   => Property::TYPE_BOOL,
        'blob'      => Property::TYPE_BLOB,
        'default'   => Property::TYPE_STRING
    );

    const ANNOTATION_PK = 'pk';
    const ANNOTATION_PROPERTY = 'param';
    const ANNOTATION_STORAGE_CLASS = 'storage';
    const ANNOTATION_COLLECTION = 'collection';

    const PROPERTY_ATTRIBUTE_NAME = 'name';
    const PROPERTY_ATTRIBUTE_REQUIRED = 'required';
    const PROPERTY_ATTRIBUTE_TYPE = 'type';

    const SCHEMA_CLASS_POSTFIX = 'Schema';

    const CLASS_TEMPLATE = <<<CLASS
%s
/**
 * Class generated automatically by \alchemy\storage\SchemaBuilder
 * DO NOT CHANGE THIS MANUALLY
 */
final class %s implements \alchemy\storage\ISchema, \Iterator
{
    public function __construct()
    {
        %s
    }

    public function getPropertyList()
    {
        return \$this->propertyNameList;
    }

    public function __get(\$name)
    {
        return \$this->propertyList[\$name];
    }

    public function getProperty(\$name)
    {
        return \$this->propertyList[\$name];
    }

    public function getPKProperty()
    {
        return \$this->propertyList[\$this->pk];
    }

    public function getCollectionName()
    {
        return \$this->collectionName;
    }

    public function propertyExists(\$name)
    {
        return isset(\$this->propertyList[\$name]);
    }

    public function getStorageClass()
    {
        return \$this->storageClass;
    }
    public function rewind()
    {
        reset(\$this->propertyList);
    }
    public function current()
    {
        return current(\$this->propertyList);
    }
    public function key()
    {
        return key(\$this->propertyList);
    }
    public function next()
    {
        return next(\$this->propertyList);
    }
    public function valid()
    {
        \$key = key(\$this->propertyList);
        return \$key !== NULL && \$key !== FALSE;
    }

    public function getModelClass()
    {
        return \$this->modelClass;
    }

    protected \$pk = '%s';
    protected \$propertyList = array();
    protected \$storageClass = '%s';
    protected \$propertyNameList = array(%s);
    protected \$collectionName = '%s';
    protected \$modelClass = '%s';
}
CLASS;

}
