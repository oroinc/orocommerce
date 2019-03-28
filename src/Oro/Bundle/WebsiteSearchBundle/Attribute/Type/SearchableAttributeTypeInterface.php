<?php

namespace Oro\Bundle\WebsiteSearchBundle\Attribute\Type;

use Oro\Bundle\EntityConfigBundle\Attribute\Type\AttributeTypeInterface;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;

/**
 * Attribute type that can be used at website search index to filter and sort data.
 */
interface SearchableAttributeTypeInterface extends AttributeTypeInterface
{
    const FILTER_TYPE_NUMBER_RANGE = 'number-range';
    const FILTER_TYPE_STRING = 'string';
    const FILTER_TYPE_ENUM = 'enum';
    const FILTER_TYPE_MULTI_ENUM = 'multi-enum';
    const FILTER_TYPE_PERCENT = 'percent';
    const FILTER_TYPE_ENTITY = 'entity';

    /**
     * @return string
     *
     * Can be 'integer', 'decimal', 'text' or 'datetime'
     */
    public function getFilterStorageFieldType();

    /**
     * @return string
     *
     * Can be 'integer', 'decimal', 'text' or 'datetime'
     */
    public function getSorterStorageFieldType();

    /**
     * @return string
     *
     * Can be 'number', 'number-range', 'string' or 'enum'
     */
    public function getFilterType();

    /**
     * @param FieldConfigModel $attribute
     *
     * @return bool
     */
    public function isLocalizable(FieldConfigModel $attribute);

    /**
     * @param FieldConfigModel $attribute
     *
     * @return string
     */
    public function getFilterableFieldName(FieldConfigModel $attribute);

    /**
     * @param FieldConfigModel $attribute
     *
     * @return string
     */
    public function getSortableFieldName(FieldConfigModel $attribute);
}
