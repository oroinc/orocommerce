<?php

namespace OroB2B\Bundle\PricingBundle\Provider;

use Doctrine\Bundle\DoctrineBundle\Registry;
use Doctrine\Common\Persistence\Mapping\ClassMetadata;

use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;

class PriceRuleAttributeProvider
{
    const FIELD_TYPE_NATIVE = 'native';
    const FIELD_TYPE_VIRTUAL = 'virtual';

    /**
     * @var array
     */
    static protected $supportedTypes = [
        'integer' => true,
        'float' => true,
        'money' => true,
        'decimal' => true
    ];

    /**
     * @var EntityFieldProvider
     */
    protected $entityFieldProvider;

    /**
     * @var Registry
     */
    protected $registry;

    /**
     * @var array
     */
    protected $supportedClasses = [];

    /**
     * @var array
     */
    protected $availableRuleAttributes;

    /**
     * @var array
     */
    protected $availableConditionAttributes;

    /**
     * @var array
     */
    protected $fieldsCache;

    /**
     * @param Registry $registry
     * @param EntityFieldProvider $entityFieldProvider
     */
    public function __construct(Registry $registry, EntityFieldProvider $entityFieldProvider)
    {
        $this->registry = $registry;
        $this->entityFieldProvider = $entityFieldProvider;
    }

    /**
     * @param string $className
     * @return array
     * @throws \Exception
     */
    public function getAvailableRuleAttributes($className)
    {
        if (!$this->isClassSupported($className)) {
            throw new \Exception('Class does not supported');
        }
        $this->ensureRuleAttributes();

        return $this->availableRuleAttributes[$className];
    }

    /**
     * @param string $className
     * @return array
     * @throws \Exception
     */
    public function getAvailableConditionAttributes($className)
    {
        if (!$this->isClassSupported($className)) {
            throw new \Exception('Class does not supported');
        }
        $this->ensureConditionAttributes();

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
        if (count($classNameInfo) > 1) {
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

    protected function ensureRuleAttributes()
    {
        if ($this->availableRuleAttributes === null) {
            $this->availableRuleAttributes = [];

            foreach ($this->getSupportedClasses() as $class) {
                $fields = $this->getClassFields($class);
                $fields = array_filter($fields, function ($field) {
                    return !empty(self::$supportedTypes[$field['data_type']]);
                });
                $this->availableRuleAttributes[$class] = $fields;
            }
        }
    }

    protected function ensureConditionAttributes()
    {
        if ($this->availableConditionAttributes === null) {
            $this->availableConditionAttributes = [];
            foreach ($this->getSupportedClasses() as $class) {
                $this->availableConditionAttributes[$class] = $this->getClassFields($class);
            }
        }
    }

    /**
     * @param $class
     * @return array
     */
    protected function getClassFields($class)
    {
        $fields = [];
        /** @var ClassMetadata $metadata */
        $metadata = $this->registry
            ->getManagerForClass($class)
            ->getClassMetadata($class);

        foreach ($metadata->getFieldNames() as $fieldName) {
            $dataType = $metadata->getTypeOfField($fieldName);
            $field = ['name' => $fieldName, 'type' => self::FIELD_TYPE_NATIVE, 'data_type' => $dataType];
            $fields[$fieldName] = $field;
        }

        $virtualFields = $this->entityFieldProvider->getVirtualFields($class);
        foreach ($virtualFields as $fieldName) {
            $fieldQuery = $this->entityFieldProvider->getVirtualFieldQuery($class, $fieldName);
            $dataType = $fieldQuery['select']['return_type'];
            $field = ['name' => $fieldName, 'type' => self::FIELD_TYPE_VIRTUAL, 'data_type' => $dataType];
            $fields[$fieldName] = $field;
        }

        return $fields;
    }
}
