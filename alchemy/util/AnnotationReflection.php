<?php
/**
 * Alchemy Framework (http://alchemyframework.org/)
 *
 * @link      http://github.com/dkraczkowski/alchemy for the canonical source repository
 * @copyright Copyright (c) 2012-2013 Dawid Kraczkowski
 * @license   https://raw.github.com/dkraczkowski/alchemy/master/LICENSE New BSD License
 */
namespace alchemy\util;
use alchemy\util\annotation\Parser;
/**
 * Annotation
 *
 */

final class AnnotationReflection
{
    /**
     * Creates annotation reflection object
     *
     * @param mixed $class classname or object
     */
    public function __construct($class)
    {
        if (is_object($class)) {
            $class = get_class($class);
        }
        $this->reflectionClass = new \ReflectionClass($class);

        //reflect class annotation
        $this->classAnnotation = Parser::parse($this->reflectionClass->getDocComment());

        //reflect method annotation
        foreach ($this->reflectionClass->getMethods() as $m) {
            if ($m->getDeclaringClass()->getName() != $class) {
                continue;
            }
            $this->declaredMethods[] = $m->getName();
            $this->methodsAnnotation[$m->getName()] = Parser::parse($m->getDocComment());
        }

        //reflect properties annotation
        foreach ($this->reflectionClass->getProperties() as $p) {
            if ($p->getDeclaringClass()->getName() != $class) {
                continue;
            }
            $this->declaredProperties[] = $p->getName();
            $this->propertiesAnnotation[$p->getName()] = Parser::parse($p->getDocComment());
        }
    }

    /**
     * Gets declared methods in given class
     *
     * @return array
     */
    public function getDeclaredMethods()
    {
        return $this->declaredMethods;
    }

    /**
     * Gets declared properties in given class
     *
     * @return array
     */
    public function getDeclaredProperties()
    {
        return $this->declaredProperties;
    }

    /**
     * Gets class annotations
     *
     * @return array
     */
    public function getFromClass()
    {
        return $this->classAnnotation;
    }

    /**
     * Gets class method's annotation
     *
     * @param string $name method name
     * @return mixed
     */
    public function getFromMethod($name)
    {
        if (isset($this->methodsAnnotation[$name])) {
            return $this->methodsAnnotation[$name];
        }
    }

    /**
     * Gets class property's annotation
     *
     * @param string $name property name
     * @return mixed
     */
    public function getFromProperty($name)
    {
        if (isset($this->propertiesAnnotation[$name])) {
            return $this->propertiesAnnotation[$name];
        }
    }

    protected $reflectionClass;
    protected $classAnnotation = array();
    protected $methodsAnnotation = array();
    protected $propertiesAnnotation = array();

    private $declaredMethods = array();
    private $declaredProperties = array();
}
