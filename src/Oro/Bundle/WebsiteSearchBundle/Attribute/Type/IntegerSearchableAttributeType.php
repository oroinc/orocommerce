<?php

namespace Oro\Bundle\WebsiteSearchBundle\Attribute\Type;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\SearchBundle\Query\Query;

/**
 * Attribute type provides metadata for integer attribute for search index.
 */
class IntegerSearchableAttributeType extends AbstractSearchableAttributeType
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
     * {@inheritdoc}
     */
    public function getFilterType(FieldConfigModel $attribute): string
    {
        return self::FILTER_TYPE_NUMBER_RANGE;
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
        return $attribute->getFieldName();
    }

    /**
     * {@inheritdoc}
     */
    public function getSortableFieldName(FieldConfigModel $attribute): string
    {
        return $attribute->getFieldName();
    }

    /**
     * {@inheritdoc}
     */
    public function getSearchableFieldName(FieldConfigModel $attribute): string
    {
        throw new \RuntimeException('Not supported');
    }
}
