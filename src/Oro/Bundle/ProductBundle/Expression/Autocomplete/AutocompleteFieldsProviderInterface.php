<?php

namespace Oro\Bundle\ProductBundle\Expression\Autocomplete;

/**
 * Defines the interface for AutocompleteFieldsProvider functionality
 */
interface AutocompleteFieldsProviderInterface
{
    public const TYPE_RELATION = 'relation';
    public const TYPE_INTEGER = 'integer';
    public const TYPE_STRING = 'string';
    public const TYPE_FLOAT = 'float';
    public const TYPE_BOOLEAN = 'boolean';
    public const TYPE_DATE = 'date';
    public const TYPE_DATETIME = 'datetime';
    public const TYPE_ENUM = 'enum';

    /**
     * @param string $className
     * @param string $fieldName
     * @param array $information
     */
    public function addSpecialFieldInformation($className, $fieldName, array $information);

    /**
     * @param bool $numericalOnly
     * @param bool $withRelations
     * @return array
     */
    public function getDataProviderConfig($numericalOnly = false, $withRelations = true);

    /**
     * @return array
     */
    public function getRootEntities();
}
