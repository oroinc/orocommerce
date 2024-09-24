<?php

namespace Oro\Bundle\WebsiteSearchBundle\Attribute\Type;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\SearchBundle\Query\Query;

/**
 * Attribute type provides metadata for date attribute for search index.
 */
class DateSearchableAttributeType extends AbstractSearchableAttributeType
{
    #[\Override]
    public function getFilterStorageFieldTypes(FieldConfigModel $attribute): array
    {
        throw new \RuntimeException('Not supported');
    }

    #[\Override]
    public function getSorterStorageFieldType(FieldConfigModel $attribute): string
    {
        return Query::TYPE_DATETIME;
    }

    #[\Override]
    public function getFilterType(FieldConfigModel $attribute): string
    {
        throw new \RuntimeException('Not supported');
    }

    #[\Override]
    public function isLocalizable(FieldConfigModel $attribute): bool
    {
        return false;
    }

    #[\Override]
    public function getFilterableFieldNames(FieldConfigModel $attribute): array
    {
        throw new \RuntimeException('Not supported');
    }

    #[\Override]
    public function getSortableFieldName(FieldConfigModel $attribute): string
    {
        return $attribute->getFieldName();
    }

    #[\Override]
    public function getSearchableFieldName(FieldConfigModel $attribute): string
    {
        throw new \RuntimeException('Not supported');
    }
}
