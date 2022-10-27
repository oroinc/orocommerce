<?php

namespace Oro\Bundle\CMSBundle\Form\Handler;

use Oro\Bundle\CMSBundle\DBAL\Types\WYSIWYGPropertiesType;
use Oro\Bundle\CMSBundle\DBAL\Types\WYSIWYGStyleType;
use Oro\Bundle\CMSBundle\DBAL\Types\WYSIWYGType;
use Oro\Bundle\EntityConfigBundle\Entity\ConfigModel;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Form\Handler\CreateUpdateConfigFieldHandler as BaseCreateUpdateConfigFieldHandler;

/**
 * Extend Entity Config CreateUpdateConfigFieldHandler functional.
 * Add possibility to create FieldConfigModel for WYSIWYG additional fields.
 */
class CreateUpdateConfigFieldHandler extends BaseCreateUpdateConfigFieldHandler
{
    protected function createAndUpdateFieldModel(
        string $entityClassName,
        string $fieldName,
        string $fieldType,
        array $fieldOptions
    ): FieldConfigModel {
        $newFieldModel = parent::createAndUpdateFieldModel(
            $entityClassName,
            $fieldName,
            $fieldType,
            $fieldOptions
        );

        if ($fieldType === WYSIWYGType::TYPE) {
            $this->createWYSIWYGFieldModel($entityClassName, $fieldName, $fieldOptions);
        }

        return $newFieldModel;
    }

    private function createWYSIWYGFieldModel(
        string $entityClassName,
        string $fieldName,
        array $fieldOptions
    ) {
        $wysiwygFields[WYSIWYGStyleType::TYPE_SUFFIX] = WYSIWYGStyleType::TYPE;
        $wysiwygFields[WYSIWYGPropertiesType::TYPE_SUFFIX] = WYSIWYGPropertiesType::TYPE;

        foreach ($wysiwygFields as $suffix => $type) {
            $newWysiwygFieldModel = $this->configManager->createConfigFieldModel(
                $entityClassName,
                $fieldName . $suffix,
                $type,
                ConfigModel::MODE_HIDDEN
            );

            $additionalFieldOptions = $fieldOptions;
            if ($this->isAttributeOptions($fieldOptions)) {
                $attributeFieldName = $fieldOptions['attribute']['field_name'] ?? $fieldName;

                $additionalFieldOptions['attribute']['field_name'] = $attributeFieldName . $suffix;
                $additionalFieldOptions['attribute']['is_attribute'] = false;
                $additionalFieldOptions['extend']['is_serialized'] = true;
            }

            $this->configHelper->updateFieldConfigs($newWysiwygFieldModel, $additionalFieldOptions);
        }
    }

    private function isAttributeOptions(array $fieldOptions): bool
    {
        return array_key_exists('attribute', $fieldOptions)
            && array_key_exists('is_attribute', $fieldOptions['attribute'])
            && $fieldOptions['attribute']['is_attribute'];
    }
}
