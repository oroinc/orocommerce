<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Oro\Bundle\EntityConfigBundle\Attribute\Entity\AttributeFamily;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Manager\AttributeManager;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;

/**
 * Data Provider that returns variant fields (attributes with restrictions)
 */
class VariantFieldProvider
{
    private array $allowedAttributeTypes = ['boolean', 'enum'];

    public function __construct(
        private AttributeManager $attributeManager,
        private SerializedFieldProvider $serializedFieldProvider
    ) {
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
            if (
                !in_array($attribute->getType(), $this->allowedAttributeTypes, true)
                || $this->attributeManager->isSystem($attribute)
                || !$this->attributeManager->isActive($attribute)
                || ($this->serializedFieldProvider->isSerialized($attribute)
                    && !ExtendHelper::isEnumerableType($attribute->getType()))
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
