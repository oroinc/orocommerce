<?php

namespace OroB2B\Bundle\ProductBundle\Provider;

use Oro\Bundle\EntityBundle\Provider\EntityFieldProvider;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

class CustomFieldProvider
{
    /**
     * @var ConfigProvider
     */
    protected $extendConfigProvider;

    /**
     * @var EntityFieldProvider
     */
    protected $entityFieldProvider;

    /**
     * @param ConfigProvider $extendConfigProvider
     * @param EntityFieldProvider $entityFieldProvider
     */
    public function __construct(ConfigProvider $extendConfigProvider, EntityFieldProvider $entityFieldProvider)
    {
        $this->extendConfigProvider = $extendConfigProvider;
        $this->entityFieldProvider = $entityFieldProvider;
    }

    /**
     * @param string $entityName
     * @return array
     */
    public function getEntityCustomFields($entityName)
    {
        $result = [];
        $allFields = $this->getEntityFields($entityName);
        $configCustomFields = $this->getEntityCustomFieldsFromConfig($entityName);

        foreach ($configCustomFields as $field) {
            if (array_key_exists($field, $allFields)) {
                $result[$field] = $allFields[$field];
            }
        }

        return $result;
    }

    /**
     * @param string $entityName
     * @return array
     */
    private function getEntityFields($entityName)
    {
        $result = [];
        $fields = $this->entityFieldProvider->getFields($entityName);

        foreach ($fields as $field) {
            $result[$field['name']] = $field;
        }

        return $result;
    }

    /**
     * @param string $entityName
     * @return array
     */
    private function getEntityCustomFieldsFromConfig($entityName)
    {
        $extendConfig = $this->extendConfigProvider->getConfig($entityName);
        $schema = $extendConfig->get('schema');
        $customFields = $schema['property'];

        return $customFields;
    }
}
