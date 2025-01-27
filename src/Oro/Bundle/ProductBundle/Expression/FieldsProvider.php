<?php

namespace Oro\Bundle\ProductBundle\Expression;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;
use Oro\Bundle\EntityExtendBundle\Entity\EnumOption;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Oro\Component\Expression\FieldsProviderInterface;

/**
 * Provides information about entity fields.
 */
class FieldsProvider implements FieldsProviderInterface
{
    /**
     * @var array
     */
    protected static $supportedNumericTypes = [
        'integer' => true,
        'float' => true,
        'money' => true,
        'decimal' => true,
    ];

    /**
     * @var array
     */
    protected static $supportedRelationTypes = [
        'ref-one' => true,
        'manyToOne' => true
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
     * @var array
     */
    protected $fieldsWhiteList = [];

    /**
     * @var array
     */
    protected $fieldsBlackList = [];

    public function __construct(EntityFieldProvider $entityFieldProvider, DoctrineHelper $doctrineHelper)
    {
        $this->entityFieldProvider = $entityFieldProvider;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param string $className
     * @param string $fieldName
     */
    public function addFieldToWhiteList($className, $fieldName)
    {
        $this->fieldsWhiteList[$className][$fieldName] = true;
    }

    /**
     * @param string $className
     * @param string $fieldName
     */
    public function addFieldToBlackList($className, $fieldName)
    {
        $this->fieldsBlackList[$className][$fieldName] = true;
    }

    #[\Override]
    public function getFields($className, $numericOnly = false, $withRelations = false)
    {
        $realClassName = $this->getRealClassName($className);
        $fields = $this->getDetailedFieldsInformation($realClassName, $numericOnly, $withRelations);

        return array_keys($fields);
    }

    #[\Override]
    public function isRelation($className, $fieldName)
    {
        $field = $this->getField($className, $fieldName);

        return !empty($field['relation_type']);
    }

    #[\Override]
    public function getIdentityFieldName($className)
    {
        return $this->doctrineHelper->getSingleEntityIdentifierFieldName($className, false);
    }

    #[\Override]
    public function getRealClassName($className, $fieldName = null)
    {
        if (!$fieldName && str_contains($className, '::')) {
            [$className, $fieldName] = explode('::', $className);
        }

        if ($fieldName) {
            $numericOnly = false;
            $withRelations = true;
            $fields = $this->getDetailedFieldsInformation($className, $numericOnly, $withRelations);
            if (\array_key_exists($fieldName, $fields)) {
                $className = !ExtendHelper::isEnumerableType($fields[$fieldName]['type'])
                    ? $fields[$fieldName]['related_entity_name']
                    : EnumOption::class;
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
     * @return array
     */
    #[\Override]
    public function getDetailedFieldsInformation($className, $numericOnly = false, $withRelations = false)
    {
        $cacheKey = $this->getCacheKey($className, $numericOnly, $withRelations);
        if (!array_key_exists($cacheKey, $this->entityFields)) {
            $options = EntityFieldProvider::OPTION_APPLY_EXCLUSIONS
                | EntityFieldProvider::OPTION_TRANSLATE
                | EntityFieldProvider::OPTION_WITH_RELATIONS
                | EntityFieldProvider::OPTION_WITH_VIRTUAL_FIELDS;
            $fields = $this->entityFieldProvider->getEntityFields($className, $options);
            $this->entityFields[$cacheKey] = [];
            foreach ($fields as $field) {
                if (!$withRelations && !empty($field['relation_type'])) {
                    continue;
                }

                $fieldName = $field['name'];
                if ($this->isBlacklistedField($className, $fieldName)
                    || (
                        !$this->isWhitelistedField($className, $fieldName)
                        && $this->isSkippedField($field, $numericOnly, $withRelations)
                    )
                ) {
                    continue;
                }
                $this->entityFields[$cacheKey][$fieldName] = $field;
            }
        }

        return $this->entityFields[$cacheKey];
    }

    /**
     * @param array $field
     * @param bool $numericOnly
     * @param bool $withRelations
     * @return bool
     */
    protected function isSkippedField(array $field, $numericOnly, $withRelations)
    {
        $isDisallowedNumeric = $numericOnly
            && empty($field['relation_type'])
            && empty(self::$supportedNumericTypes[$field['type']]);
        $isDisallowedRelation = $withRelations && $this->isUnsupportedRelation($field);
        $isMultiEnum = $field['type'] === 'multiEnum';

        return $isDisallowedNumeric || $isDisallowedRelation || $isMultiEnum;
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

    /**
     * @param string $className
     * @param string $fieldName
     * @return null|array
     */
    protected function getField($className, $fieldName)
    {
        $entityFields = $this->getDetailedFieldsInformation($className, false, true);
        if (array_key_exists($fieldName, $entityFields)) {
            return $entityFields[$fieldName];
        }

        return null;
    }

    /**
     * @return array
     */
    #[\Override]
    public function getSupportedNumericTypes()
    {
        return array_keys(self::$supportedNumericTypes);
    }

    /**
     * @param array $field
     * @return bool
     */
    protected function isUnsupportedRelation(array $field)
    {
        return array_key_exists('relation_type', $field)
            && empty(self::$supportedRelationTypes[$field['relation_type']]);
    }

    /**
     * @return array
     */
    #[\Override]
    public function getSupportedRelationTypes()
    {
        return array_keys(self::$supportedRelationTypes);
    }

    /**
     * @param string $className
     * @param string $fieldName
     * @return bool
     */
    protected function isWhitelistedField($className, $fieldName)
    {
        return !empty($this->fieldsWhiteList[$className][$fieldName]);
    }

    /**
     * @return array
     */
    #[\Override]
    public function getFieldsWhiteList()
    {
        return $this->fieldsWhiteList;
    }

    /**
     * @param string $className
     * @param string $fieldName
     * @return bool
     */
    protected function isBlacklistedField($className, $fieldName)
    {
        return !empty($this->fieldsBlackList[$className][$fieldName]);
    }

    /**
     * @return array
     */
    #[\Override]
    public function getFieldsBlackList()
    {
        return $this->fieldsBlackList;
    }
}
