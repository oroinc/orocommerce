<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Manager\AttributeManager;
use Oro\Bundle\EntityConfigBundle\Provider\SerializedFieldProvider;

class VariantFieldProvider
{
    /** @var AttributeManager */
    private $attributeManager;

    /** @var SerializedFieldProvider */
    private $serializedFieldsProvider;

    /** @var array */
    private $allowedAttributeTypes = ['boolean', 'enum'];

    /**
     * @param AttributeManager $attributeManager
     * @param SerializedFieldProvider $serializedFieldProvider
     */
    public function __construct(AttributeManager $attributeManager, SerializedFieldProvider $serializedFieldProvider)
    {
        $this->attributeManager = $attributeManager;
        $this->serializedFieldsProvider = $serializedFieldProvider;
    }

    /**
     * @param AttributeFamily $attributeFamily
     * @return VariantField[]
     */
    public function getVariantFields(AttributeFamily $attributeFamily)
    {
        $variantFields = [];
        $attributes = $this->attributeManager->getAttributesByFamily($attributeFamily);

        /** @var FieldConfigModel $attribute */
        foreach ($attributes as $attribute) {
            //Leave only attributes which meets requirements
            if (!in_array($attribute->getType(), $this->allowedAttributeTypes, true) ||
                $this->attributeManager->isSystem($attribute) ||
                !$this->attributeManager->isActive($attribute) ||
                $this->serializedFieldsProvider->isSerialized($attribute)
            ) {
                continue;
            }

            $fieldName = $attribute->getFieldName();
            $variantFields[$fieldName] = new VariantField(
                $fieldName,
                $this->attributeManager->getAttributeLabel($attribute)
            );
        }

        return $variantFields;
    }
}
