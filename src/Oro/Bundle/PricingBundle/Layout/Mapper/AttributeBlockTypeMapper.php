<?php

namespace Oro\Bundle\PricingBundle\Layout\Mapper;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Layout\Mapper\AbstractAttributeBlockTypeMapper;

class AttributeBlockTypeMapper extends AbstractAttributeBlockTypeMapper
{
    /** @var array */
    protected $attributeNamesRegistry = [];

    /**
     * @param string $fieldName
     * @param string $blockType
     *
     * @return AbstractAttributeBlockTypeMapper
     */
    public function addBlockTypeByFieldName($fieldName, $blockType)
    {
        $this->attributeNamesRegistry[$fieldName] = $blockType;

        return $this;
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockType(FieldConfigModel $attribute)
    {
        $fieldName = $attribute->getFieldName();
        if (array_key_exists($fieldName, $this->attributeNamesRegistry)) {
            return $this->attributeNamesRegistry[$fieldName];
        }

        return parent::getBlockType($attribute);
    }
}
