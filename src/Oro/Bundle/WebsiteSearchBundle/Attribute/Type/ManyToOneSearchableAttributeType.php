<?php

namespace Oro\Bundle\WebsiteSearchBundle\Attribute\Type;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\LocalizationIdPlaceholder;

/**
 * Attribute type provides metadata for manyToOne attribute for search index.
 */
class ManyToOneSearchableAttributeType extends AbstractSearchableAttributeType
{
    #[\Override]
    protected function getFilterStorageFieldTypeMain(FieldConfigModel $attribute): string
    {
        return Query::TYPE_INTEGER;
    }

    #[\Override]
    public function getSorterStorageFieldType(FieldConfigModel $attribute): string
    {
        return Query::TYPE_TEXT;
    }

    #[\Override]
    public function getFilterType(FieldConfigModel $attribute): string
    {
        return self::FILTER_TYPE_ENTITY;
    }

    #[\Override]
    public function isLocalizable(FieldConfigModel $attribute): bool
    {
        return true;
    }

    #[\Override]
    protected function getFilterableFieldNameMain(FieldConfigModel $attribute): string
    {
        return $attribute->getFieldName();
    }

    #[\Override]
    public function getSortableFieldName(FieldConfigModel $attribute): string
    {
        return $attribute->getFieldName() . '_' . LocalizationIdPlaceholder::NAME;
    }

    #[\Override]
    public function getSearchableFieldName(FieldConfigModel $attribute): string
    {
        return $this->getSortableFieldName($attribute);
    }
}
