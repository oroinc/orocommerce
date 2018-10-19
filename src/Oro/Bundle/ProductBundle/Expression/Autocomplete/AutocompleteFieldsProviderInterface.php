<?php

namespace Oro\Bundle\ProductBundle\Expression\Autocomplete;

/**
 * Defines the interface for AutocompleteFieldsProvider functionality
 */
interface AutocompleteFieldsProviderInterface
{
    const TYPE_RELATION = 'relation';
    const TYPE_INTEGER = 'integer';
    const TYPE_STRING = 'string';
    const TYPE_FLOAT = 'float';
    const TYPE_BOOLEAN = 'boolean';
    const TYPE_DATE = 'date';
    const TYPE_DATETIME = 'datetime';
    const TYPE_ENUM = 'enum';

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
