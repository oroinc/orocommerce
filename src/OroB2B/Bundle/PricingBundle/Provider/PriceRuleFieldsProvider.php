<?php

namespace OroB2B\Bundle\PricingBundle\Provider;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
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
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var array
     */
    protected $entityFields = [];

    /**
     * @param EntityFieldProvider $entityFieldProvider
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(EntityFieldProvider $entityFieldProvider, DoctrineHelper $doctrineHelper)
    {
        $this->entityFieldProvider = $entityFieldProvider;
        $this->doctrineHelper = $doctrineHelper;
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
        $fields = $this->getEntityFields($realClassName, $numericOnly, $withRelations);

        return array_keys($fields);
    }

    /**
     * @param string $className
     * @param string $fieldName
     * @return null|array
     */
    public function getField($className, $fieldName)
    {
        $entityFields = $this->getEntityFields($className, false, true);
        if (array_key_exists($fieldName, $entityFields)) {
            return $entityFields[$fieldName];
        }
        
        return null;
    }

    /**
     * @param string $className
     * @param string $fieldName
     * @return bool
     */
    public function isRelation($className, $fieldName)
    {
        $field = $this->getField($className, $fieldName);

        return !empty($field['relation_type']);
    }

    /**
     * @param string $className
     * @return null|string
     */
    public function getIdentityFieldName($className)
    {
        return $this->doctrineHelper->getSingleEntityIdentifierFieldName($className, false);
    }

    /**
     * @param string $className
     * @param null|string $fieldName
     * @return string
     */
    public function getRealClassName($className, $fieldName = null)
    {
        if (!$fieldName && strpos($className, '::') !== false) {
            list($className, $fieldName) = explode('::', $className);
        }
        
        if ($fieldName) {
            $numericOnly = false;
            $withRelations = true;
            $fields = $this->getEntityFields($className, $numericOnly, $withRelations);
            if (array_key_exists($fieldName, $fields)) {
                $className = $fields[$fieldName]['related_entity_name'];
            } else {
                throw new \InvalidArgumentException(
                    sprintf('Field "%s" is not found in class %s', $fieldName, $className)
                );
            }
        }

        return $className;
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
            $fields = $this->entityFieldProvider->getFields(
                $className,
                $withRelations,
                $withRelations,
                false,
                false,
                true,
                false
            );
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
        return $className . '|' . ($numericOnly ? 't' : 'f') . '|' . ($withRelations ? 't' : 'f');
    }
}
