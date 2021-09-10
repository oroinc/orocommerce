<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Doctrine\Common\Cache\ArrayCache;
use Doctrine\Common\Cache\CacheProvider;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;

/**
 * Provides ability to get list of the custom extended fields of the entity.
 */
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
     * @var CacheProvider
     */
    private $cache;

    /**
     * @var int
     */
    private $cacheLifeTime = 0;

    public function __construct(ConfigProvider $extendConfigProvider, ConfigProvider $entityConfigProvider)
    {
        $this->extendConfigProvider = $extendConfigProvider;
        $this->entityConfigProvider = $entityConfigProvider;
        $this->cache = new ArrayCache();
    }

    public function setCache(CacheProvider $cache, int $lifeTime = 0): void
    {
        $this->cache = $cache;
        $this->cacheLifeTime = $lifeTime;
    }

    /**
     * @param string $entityName
     * @return array
     */
    public function getEntityCustomFields($entityName)
    {
        $data = $this->cache->fetch($entityName);
        if (!\is_array($data)) {
            $data = $this->getActualEntityCustomFields($entityName);

            $this->cache->save($entityName, $data, $this->cacheLifeTime);
        }

        return $data;
    }

    private function getActualEntityCustomFields(string $entityName): array
    {
        $customFields = [];
        $extendConfigs = $this->extendConfigProvider->getConfigs($entityName);

        foreach ($extendConfigs as $extendConfig) {
            if ($extendConfig->get('owner') !== ExtendScope::OWNER_CUSTOM) {
                continue;
            }

            if (!$extendConfig->in('state', [ExtendScope::STATE_ACTIVE, ExtendScope::STATE_UPDATE])) {
                continue;
            }

            /** @var FieldConfigId $configId */
            $configId = $extendConfig->getId();

            $entityConfig = $this->entityConfigProvider
                ->getConfigById($configId);

            $fieldName = $configId->getFieldName();

            $customFields[$fieldName] = [
                'name' => $fieldName,
                'type' => $configId->getFieldType(),
                'label' => $entityConfig->get('label'),
                'is_serialized' => $extendConfig->get('is_serialized', false, false)
            ];
        }

        return $customFields;
    }
}
