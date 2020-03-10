<?php

namespace Oro\Bundle\CMSBundle\Provider;

use Oro\Bundle\CMSBundle\DBAL\Types\WYSIWYGType;
use Oro\Bundle\EntityConfigBundle\Config\ConfigManager;

/**
 * Provide WYSIWYG fields by entity class name
 */
class WYSIWYGFieldsProvider
{
    /**
     * @var ConfigManager
     */
    private $configManager;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * @param string $entityClass
     * @return array
     */
    public function getWysiwygFields(string $entityClass): array
    {
        $wysiwygFields = [];

        $entityConfigModel = $this->configManager->getConfigEntityModel($entityClass);
        if ($entityConfigModel) {
            foreach ($entityConfigModel->getFields() as $fieldConfigModel) {
                $fieldName = $fieldConfigModel->getFieldName();
                $fieldType = $fieldConfigModel->getType();

                if ($fieldType === WYSIWYGType::TYPE) {
                    $wysiwygFields[] = $fieldName;
                }
            }
        }

        return $wysiwygFields;
    }

    /**
     * @param string $entityClass
     * @return array
     */
    public function getWysiwygAttributes(string $entityClass): array
    {
        $wysiwygAttributes = [];

        foreach ($this->getWysiwygFields($entityClass) as $fieldName) {
            if ($this->configManager->getFieldConfig('attribute', $entityClass, $fieldName)
                ->is('is_attribute')
            ) {
                $wysiwygAttributes[] = $fieldName;
            }
        }

        return $wysiwygAttributes;
    }
}
