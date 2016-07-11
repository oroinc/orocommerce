<?php

namespace OroB2B\Bundle\PricingBundle\Provider;

use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;

class PriceRuleAttributeProvider
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
    protected $availableRuleAttributes = [];

    /**
     * @var array
     */
    protected $availableConditionAttributes = [];

    /**
     * @var array
     */
    protected $fieldsCache = [];

    /**
     * @param EntityFieldProvider $entityFieldProvider
     */
    public function __construct(EntityFieldProvider $entityFieldProvider)
    {
        $this->entityFieldProvider = $entityFieldProvider;
    }

    /**
     * @param string $className
     * @return array
     * @throws \Exception
     */
    public function getAvailableRuleAttributes($className)
    {
        $realClassName = $this->getRealClassName($className);
        if (!$this->isClassSupported($className)) {
            throw new \Exception('Class does not supported');
        }
        $this->ensureRuleAttributes($realClassName);

        return $this->availableRuleAttributes[$className];
    }

    /**
     * @param string $className
     * @return array
     * @throws \Exception
     */
    public function getAvailableConditionAttributes($className)
    {
        $realClassName = $this->getRealClassName($className);
        if (!$this->isClassSupported($className)) {
            throw new \Exception('Class does not supported');
        }
        $this->ensureConditionAttributes($realClassName);

        return $this->availableConditionAttributes[$className];
    }

    /**
     * @param $className
     * @return string
     */
    public function getRealClassName($className)
    {
        $classNameInfo = explode("::", $className);
        $realClassName = $classNameInfo[0];
        if (!count($classNameInfo) > 1) {
            $fieldName = $classNameInfo[1];
            if (!array_key_exists($realClassName, $this->fieldsCache)) {
                $this->fieldsCache[$realClassName] = $this->entityFieldProvider->getFields($realClassName, true, true);
            }
            $fields = $this->fieldsCache[$realClassName];
            foreach ($fields as $field) {
                if ($field['name'] === $fieldName) {
                    $realClassName = $field['related_entity_name'];
                    break;
                }
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
     * @return array|string[]
     */
    public function getSupportedClasses()
    {
        return array_keys($this->supportedClasses);
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
     */
    protected function ensureRuleAttributes($className)
    {
        if (!array_key_exists($className, $this->availableRuleAttributes)) {
            if (!array_key_exists($className, $this->fieldsCache)) {
                $this->fieldsCache[$className] = $this->entityFieldProvider->getFields($className, true, true);
            }
            $this->availableRuleAttributes[$className] = [];
            foreach ($this->fieldsCache[$className] as $field) {
                if (array_key_exists($field['type'], self::$supportedTypes)) {
                    $this->availableRuleAttributes[$className][] = $field['name'];
                }
            }
        }
    }

    /**
     * @param string $classNames
     */
    protected function ensureConditionAttributes($classNames)
    {
        if (!array_key_exists($classNames, $this->availableConditionAttributes)) {
            if (!array_key_exists($classNames, $this->fieldsCache)) {
                $this->fieldsCache[$classNames] = $this->entityFieldProvider->getFields($classNames, true, true);
            }
            $this->availableConditionAttributes[$classNames] = [];
            foreach ($this->fieldsCache[$classNames] as $field) {
                $this->availableConditionAttributes[$classNames][] = $field['name'];
            }
        }
    }
}
