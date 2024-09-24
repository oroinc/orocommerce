<?php

namespace Oro\Bundle\WebsiteSearchBundle\Attribute\Type;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\EnumIdPlaceholder;

/**
 * Searchable attribute type for enum field type
 */
class EnumSearchableAttributeType extends AbstractSearchableAttributeType
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

    /**
     * Enum is uses array representation as in general it may combine multiple values
     *
     */
    #[\Override]
    public function getFilterType(FieldConfigModel $attribute): string
    {
        return self::FILTER_TYPE_MULTI_ENUM;
    }

    #[\Override]
    public function isLocalizable(FieldConfigModel $attribute): bool
    {
        return false;
    }

    #[\Override]
    protected function getFilterableFieldNameMain(FieldConfigModel $attribute): string
    {
        return $attribute->getFieldName() . '_enum.' . EnumIdPlaceholder::NAME;
    }

    #[\Override]
    public function getSortableFieldName(FieldConfigModel $attribute): string
    {
        return $attribute->getFieldName() . '_priority';
    }

    #[\Override]
    public function getSearchableFieldName(FieldConfigModel $attribute): string
    {
        return $attribute->getFieldName() . '_' . self::SEARCHABLE_SUFFIX;
    }
}
