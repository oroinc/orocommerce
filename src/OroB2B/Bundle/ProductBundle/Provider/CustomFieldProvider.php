<?php

namespace OroB2B\Bundle\ProductBundle\Provider;

use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;

class CustomFieldProvider
{
    /**
     * @var ConfigProvider
     */
    protected $extendConfigProvider;

    /**
     * @var ConfigProvider
     */
    protected $entityConfigProvider;

    /**
     * @param ConfigProvider $extendConfigProvider
     * @param ConfigProvider $entityConfigProvider
     */
    public function __construct(ConfigProvider $extendConfigProvider, ConfigProvider $entityConfigProvider)
    {
        $this->extendConfigProvider = $extendConfigProvider;
        $this->entityConfigProvider = $entityConfigProvider;
    }

    /**
     * @param string $entityName
     * @return array
     */
    public function getEntityCustomFields($entityName)
    {
        $customFields = [];
        $extendConfigs = $this->extendConfigProvider->getConfigs($entityName);

        foreach ($extendConfigs as $extendConfig) {
            if ($extendConfig->get('owner') === 'Custom') {
                /** @var FieldConfigId $configId */
                $configId = $extendConfig->getId();
                $entityConfig = $this->entityConfigProvider->getConfigById($configId);

                $customFields[$configId->getFieldName()] = [
                    'name' => $configId->getFieldName(),
                    'type' => $configId->getFieldType(),
                    'label' => $entityConfig->get('label')
                ];
            }
        }

        return $customFields;
    }
}
