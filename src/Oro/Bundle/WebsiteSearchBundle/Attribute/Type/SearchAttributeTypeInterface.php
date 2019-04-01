<?php

namespace Oro\Bundle\WebsiteSearchBundle\Attribute\Type;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;

/**
 * Attribute type that can be used at website search index to filter and sort data.
 *
 * Introduces new methods which allow using multiple fields in filters.
 * SearchableAttributeTypeInterface will be merged to this interface and removed in v4.0.
 */
interface SearchAttributeTypeInterface extends SearchableAttributeTypeInterface
{
    public const VALUE_MAIN = 'main';
    public const VALUE_AGGREGATE = 'aggregate';

    /**
     * @return array
     *
     * Can contain 'integer', 'decimal', 'text', 'datetime'
     */
    public function getFilterStorageFieldTypes(): array;

    /**
     * @param FieldConfigModel $attribute
     *
     * @return array
     */
    public function getFilterableFieldNames(FieldConfigModel $attribute): array;
}
