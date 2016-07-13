<?php

namespace OroB2B\Bundle\PricingBundle\Provider;

use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;

class PriceRuleFieldsProvider
{
    /**
     * @var array
     */
    static protected $supportedTypes = [
        'integer' => true,
        'float' => true,
        'money' => true,
        'decimal' => true,
    ];

    /**
     * @var EntityFieldProvider
     */
    protected $entityFieldProvider;

    /**
     * @var array
     */
    protected $supportedClasses = [];

    /**
     * @var array
     */
    protected $entityFields = [];

    /**
     * @param EntityFieldProvider $entityFieldProvider
     */
    public function __construct(EntityFieldProvider $entityFieldProvider)
    {
        $this->entityFieldProvider = $entityFieldProvider;
    }

    /**
     * @param string $className
     * @param bool|false $numericOnly
     * @param bool|false $withRelations
     * @return array
     * @throws \Exception
     */
    public function getFields($className, $numericOnly = false, $withRelations = false)
    {
        $realClassName = $this->getRealClassName($className);
        if (!$this->isClassSupported($realClassName)) {
            throw new \Exception('Class does not supported');
        }
        $fields = $this->getEntityFields($realClassName, $numericOnly, $withRelations);

        return array_keys($fields);
    }

    /**
     * @param $className
     * @return string
     * @throws \Exception
     */
    public function getRealClassName($className)
    {
        $classNameInfo = explode("::", $className);
        $realClassName = $classNameInfo[0];
        if (count($classNameInfo) > 1) {
            $numericOnly = false;
            $withRelations = true;
            $fieldName = $classNameInfo[1];
            $fields = $this->getEntityFields($realClassName, $numericOnly, $withRelations);
            if (array_key_exists($fieldName, $fields)) {
                $realClassName = $fields[$fieldName]['related_entity_name'];
            } else {
                throw new \Exception('Field "' . $fieldName . '" is not found is class ' . $realClassName);
            }
        }

        return $realClassName;
    }

    /**
     * @param $className
     * @return bool
     */
    public function isClassSupported($className)
    {
        return array_key_exists($className, $this->supportedClasses);
    }

    /**
     * @param string $class
     */
    public function addSupportedClass($class)
    {
        $this->supportedClasses[$class] = true;
    }

    /**
     * @param string $className
     * @param bool $numericOnly
     * @param bool $withRelations
     * @return mixed
     */
    protected function getEntityFields($className, $numericOnly, $withRelations)
    {
        $cacheKey = $this->getCacheKey($className, $numericOnly, $withRelations);
        if (!array_key_exists($cacheKey, $this->entityFields)) {
            $fields = $this->entityFieldProvider->getFields($className, $withRelations, $withRelations);
            $this->entityFields[$cacheKey] = [];
            foreach ($fields as $field) {
                if ($numericOnly && empty(self::$supportedTypes[$field['type']])) {
                    continue;
                }
                $this->entityFields[$cacheKey][$field['name']] = $field;
            }
        }

        return $this->entityFields[$cacheKey];
    }

    /**
     * @param string $className
     * @param bool $numericOnly
     * @param bool $withRelations
     * @return string
     */
    protected function getCacheKey($className, $numericOnly, $withRelations)
    {
        return $className . "|" . ($numericOnly ? 't' : 'f') . "|" . ($withRelations ? 't' : 'f');
    }
}
