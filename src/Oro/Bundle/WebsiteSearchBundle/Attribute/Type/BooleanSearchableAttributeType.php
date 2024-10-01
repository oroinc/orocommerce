<?php

namespace Oro\Bundle\WebsiteSearchBundle\Attribute\Type;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\SearchBundle\Query\Query;

/**
 * Attribute type provides metadata for boolean attribute for search index.
 */
class BooleanSearchableAttributeType extends AbstractSearchableAttributeType
{
    #[\Override]
    protected function getFilterStorageFieldTypeMain(FieldConfigModel $attribute): string
    {
        return Query::TYPE_INTEGER;
    }

    #[\Override]
    public function getSorterStorageFieldType(FieldConfigModel $attribute): string
    {
        return Query::TYPE_INTEGER;
    }

    #[\Override]
    public function getFilterType(FieldConfigModel $attribute): string
    {
        return self::FILTER_TYPE_BOOLEAN;
    }

    #[\Override]
    public function isLocalizable(FieldConfigModel $attribute): bool
    {
        return false;
    }

    #[\Override]
    protected function getFilterableFieldNameMain(FieldConfigModel $attribute): string
    {
        return $attribute->getFieldName();
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
