<?php

namespace Oro\Bundle\ProductBundle\Expression\Autocomplete;

interface AutocompleteFieldsProviderInterface
{
    const ROOT_ENTITIES_KEY = 'root_entities';
    const FIELDS_DATA_KEY = 'fields_data';

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
    public function getAutocompleteData($numericalOnly = false, $withRelations = true);
}
