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
    /**
     * @param string $entityClassName
     * @param string $fieldName
     * @param string $fieldType
     * @param array $fieldOptions
     * @return FieldConfigModel
     */
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

    /**
     * @param string $entityClassName
     * @param string $fieldName
     * @param array $fieldOptions
     */
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
                $additionalFieldOptions['attribute']['field_name'] = $fieldOptions['attribute']['field_name'] . $suffix;
                $additionalFieldOptions['attribute']['is_attribute'] = false;
                $additionalFieldOptions['extend']['is_serialized'] = true;
            }

            $this->configHelper->updateFieldConfigs($newWysiwygFieldModel, $additionalFieldOptions);
        }
    }

    /**
     * @param array $fieldOptions
     * @return bool
     */
    private function isAttributeOptions(array $fieldOptions): bool
    {
        return array_key_exists('attribute', $fieldOptions)
            && array_key_exists('is_attribute', $fieldOptions['attribute'])
            && $fieldOptions['attribute']['is_attribute'];
    }
}
