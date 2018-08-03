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
    public function getFilterStorageFieldType()
    {
        return Query::TYPE_INTEGER;
    }

    /**
     * {@inheritdoc}
     */
    public function getSorterStorageFieldType()
    {
        return Query::TYPE_INTEGER;
    }

    /**
     * Enum is uses array representation as in general it may combine multiple values
     *
     * {@inheritdoc}
     */
    public function getFilterType()
    {
        return self::FILTER_TYPE_MULTI_ENUM;
    }

    /**
     * {@inheritdoc}
     */
    public function isLocalizable(FieldConfigModel $attribute)
    {
        return false;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilterableFieldName(FieldConfigModel $attribute)
    {
        return $attribute->getFieldName() . '_' . EnumIdPlaceholder::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getSortableFieldName(FieldConfigModel $attribute)
    {
        return $attribute->getFieldName() . '_priority';
    }
}
