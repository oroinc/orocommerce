<?php

namespace Oro\Bundle\WebsiteSearchBundle\Attribute\Type;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\SearchBundle\Query\Query;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\LocalizationIdPlaceholder;

class ManyToManySearchableAttributeType extends AbstractSearchableAttributeType
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
        return Query::TYPE_TEXT;
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
        return true;
    }

    /**
     * {@inheritdoc}
     */
    public function getFilterableFieldName(FieldConfigModel $attribute)
    {
        return $attribute->getFieldName() . '_' . LocalizationIdPlaceholder::NAME;
    }

    /**
     * {@inheritdoc}
     */
    public function getSortableFieldName(FieldConfigModel $attribute)
    {
        return $this->getFilterableFieldName($attribute);
    }
}
