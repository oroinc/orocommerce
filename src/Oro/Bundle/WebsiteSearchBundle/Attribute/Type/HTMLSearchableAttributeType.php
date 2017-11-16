<?php

namespace Oro\Bundle\WebsiteSearchBundle\Attribute\Type;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\SearchBundle\Query\Query;

class HTMLSearchableAttributeType extends AbstractSearchableAttributeType
{
    /**
     * {@inheritdoc}
     */
    public function getFilterStorageFieldType()
    {
        return Query::TYPE_TEXT;
    }

    /**
     * {@inheritdoc}
     */
    public function getSorterStorageFieldType()
    {
        throw new \RuntimeException('Not supported');
    }

    /**
     * {@inheritdoc}
     */
    public function getFilterType()
    {
        return self::FILTER_TYPE_STRING;
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
        throw new \RuntimeException('Not supported');
    }
}
