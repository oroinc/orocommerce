<?php

namespace Oro\Bundle\ProductBundle\ImportExport\Writer;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\ImportExport\Writer\AttributeWriter;

/**
 * Writer adds request_search_indexation config value before writing
 * for triggering search indexation for products that have this attribute
 */
class ProductAttributeWriter extends AttributeWriter
{
    /**
     * {@inheritdoc}
     */
    protected function setAttributeData(FieldConfigModel $fieldConfigModel)
    {
        $className = $fieldConfigModel->getEntity()->getClassName();
        $fieldName = $fieldConfigModel->getFieldName();

        $attributeConfigProvider = $this->configManager->getProvider('attribute');
        $attributeConfig = $attributeConfigProvider->getConfig($className, $fieldName);
        $attributeConfig->set('request_search_indexation', true);

        parent::setAttributeData($fieldConfigModel);
    }
}
