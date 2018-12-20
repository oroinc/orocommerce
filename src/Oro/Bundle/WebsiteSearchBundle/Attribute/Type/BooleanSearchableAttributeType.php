<?php

namespace Oro\Bundle\WebsiteSearchBundle\Attribute\Type;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\SearchBundle\Query\Query;

/**
 * Attribute type provides metadata for boolean attribute for search index.
 */
class BooleanSearchableAttributeType extends AbstractSearchableAttributeType
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
     * {@inheritdoc}
     */
    public function getFilterType()
    {
        return 'boolean';
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
        return $attribute->getFieldName();
    }

    /**
     * {@inheritdoc}
     */
    public function getSortableFieldName(FieldConfigModel $attribute)
    {
        return $attribute->getFieldName();
    }
}
