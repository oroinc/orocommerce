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
    /**
     * {@inheritdoc}
     */
    protected function getFilterStorageFieldTypeMain(FieldConfigModel $attribute): string
    {
        return Query::TYPE_INTEGER;
    }

    /**
     * {@inheritdoc}
     */
    public function getSorterStorageFieldType(FieldConfigModel $attribute): string
    {
        return Query::TYPE_INTEGER;
    }

    /**
     * Enum is uses array representation as in general it may combine multiple values
     *
     * {@inheritdoc}
     */
    public function getFilterType(FieldConfigModel $attribute): string
    {
        return self::FILTER_TYPE_MULTI_ENUM;
    }

    /**
     * {@inheritdoc}
     */
    public function isLocalizable(FieldConfigModel $attribute): bool
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    protected function getFilterableFieldNameMain(FieldConfigModel $attribute): string
    {
        return $attribute->getFieldName() . '_enum.' . EnumIdPlaceholder::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getSortableFieldName(FieldConfigModel $attribute): string
    {
        return $attribute->getFieldName() . '_priority';
    }

    /**
     * {@inheritdoc}
     */
    public function getSearchableFieldName(FieldConfigModel $attribute): string
    {
        return $attribute->getFieldName() . '_' . self::SEARCHABLE_SUFFIX;
    }
}
