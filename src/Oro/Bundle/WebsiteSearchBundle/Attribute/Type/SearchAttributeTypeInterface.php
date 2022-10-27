<?php

namespace Oro\Bundle\WebsiteSearchBundle\Attribute\Type;

use Oro\Bundle\EntityConfigBundle\Attribute\Type\AttributeTypeInterface;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;

/**
 * Attribute type that can be used at website search index to filter and sort data
 */
interface SearchAttributeTypeInterface extends AttributeTypeInterface
{
    public const FILTER_TYPE_NUMBER_RANGE = 'number-range';
    public const FILTER_TYPE_STRING = 'string';
    public const FILTER_TYPE_ENUM = 'enum';
    public const FILTER_TYPE_MULTI_ENUM = 'multi-enum';
    public const FILTER_TYPE_PERCENT = 'percent';
    public const FILTER_TYPE_ENTITY = 'entity';
    public const FILTER_TYPE_BOOLEAN = 'boolean';

    public const VALUE_MAIN = 'main';
    public const VALUE_AGGREGATE = 'aggregate';

    public const SEARCHABLE_SUFFIX = 'searchable';

    /**
     * @param FieldConfigModel $attribute
     * @return array
     * Must return an array of field types used in filtering. Out of the box possible keys could be:
     *  - "main" (mandatory)
     *  - "aggregate" (optional)
     *
     * Example:
     *  [
     *      static::VALUE_MAIN => 'integer',
     *      static::VALUE_AGGREGATE => 'text',
     *  ]
     * Can contain 'integer', 'decimal', 'text', 'datetime'.
     */
    public function getFilterStorageFieldTypes(FieldConfigModel $attribute): array;

    /**
     * @param FieldConfigModel $attribute
     * @return string
     *
     * Can be 'integer', 'decimal', 'text' or 'datetime'
     */
    public function getSorterStorageFieldType(FieldConfigModel $attribute): string;

    /**
     * @param FieldConfigModel $attribute
     * @return string
     *
     * Returns filter type that supports filtering in search index
     */
    public function getFilterType(FieldConfigModel $attribute): string;

    public function isLocalizable(FieldConfigModel $attribute): bool;

    /**
     * @param FieldConfigModel $attribute
     *
     * @return array
     * Must return an array of field names used in filtering. Out of the box possible keys could be:
     *  - "main" (mandatory)
     *  - "aggregate" (optional)
     *
     * Example:
     *  [
     *      static::VALUE_MAIN => 'sample-field-name',
     *      static::VALUE_AGGREGATE => 'sample-field-name-for-aggregation',
     *  ]
     */
    public function getFilterableFieldNames(FieldConfigModel $attribute): array;

    public function getSortableFieldName(FieldConfigModel $attribute): string;

    public function getSearchableFieldName(FieldConfigModel $attribute): string;
}
