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
    protected function getFilterStorageFieldTypeMain(): string
    {
        return Query::TYPE_INTEGER;
    }

    /**
     * {@inheritdoc}
     */
    public function getSorterStorageFieldType(): string
    {
        return Query::TYPE_INTEGER;
    }

    /**
     * Enum is uses array representation as in general it may combine multiple values
     *
     * {@inheritdoc}
     */
    public function getFilterType(): string
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
        return $attribute->getFieldName() . '_' . EnumIdPlaceholder::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getSortableFieldName(FieldConfigModel $attribute): string
    {
        return $attribute->getFieldName() . '_priority';
    }
}
