<?php

namespace Oro\Bundle\WebsiteSearchBundle\Attribute\Type;

use Oro\Bundle\EntityConfigBundle\Attribute\Type\AttributeTypeInterface;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\LocaleBundle\Entity\Localization;

abstract class AbstractSearchableAttributeType implements SearchableAttributeTypeInterface
{
    /** @var AttributeTypeInterface */
    protected $attributeType;

    /**
     * @param AttributeTypeInterface $attributeType
     */
    public function __construct(AttributeTypeInterface $attributeType)
    {
        $this->attributeType = $attributeType;
    }

    /**
     * {@inheritdoc}
     */
    public function getType()
    {
        return $this->attributeType->getType();
    }

    /**
     * {@inheritdoc}
     */
    public function isSearchable(FieldConfigModel $attribute = null)
    {
        return $this->attributeType->isSearchable($attribute);
    }

    /**
     * {@inheritdoc}
     */
    public function isFilterable(FieldConfigModel $attribute = null)
    {
        return $this->attributeType->isFilterable($attribute);
    }

    /**
     * {@inheritdoc}
     */
    public function isSortable(FieldConfigModel $attribute = null)
    {
        return $this->attributeType->isSortable($attribute);
    }

    /**
     * {@inheritdoc}
     */
    public function getSearchableValue(FieldConfigModel $attribute, $originalValue, Localization $localization = null)
    {
        return $this->attributeType->getSearchableValue($attribute, $originalValue, $localization);
    }

    /**
     * {@inheritdoc}
     */
    public function getFilterableValue(FieldConfigModel $attribute, $originalValue, Localization $localization = null)
    {
        return $this->attributeType->getFilterableValue($attribute, $originalValue, $localization);
    }

    /**
     * {@inheritdoc}
     */
    public function getSortableValue(FieldConfigModel $attribute, $originalValue, Localization $localization = null)
    {
        return $this->attributeType->getSortableValue($attribute, $originalValue, $localization);
    }
}
