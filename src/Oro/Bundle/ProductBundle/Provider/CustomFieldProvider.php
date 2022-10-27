<?php

namespace Oro\Bundle\ProductBundle\Provider;

use Oro\Bundle\CacheBundle\Generator\UniversalCacheKeyGenerator;
use Oro\Bundle\EntityConfigBundle\Config\Id\FieldConfigId;
use Oro\Bundle\EntityConfigBundle\Provider\ConfigProvider;
use Oro\Bundle\EntityExtendBundle\EntityConfig\ExtendScope;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Provides ability to get list of the custom extended fields of the entity.
 */
class CustomFieldProvider
{
    private ConfigProvider $extendConfigProvider;
    private ConfigProvider $entityConfigProvider;
    private CacheInterface $cache;
    private int $cacheLifeTime = 0;

    public function __construct(ConfigProvider $extendConfigProvider, ConfigProvider $entityConfigProvider)
    {
        $this->extendConfigProvider = $extendConfigProvider;
        $this->entityConfigProvider = $entityConfigProvider;
        $this->cache = new ArrayAdapter($this->cacheLifeTime, false);
    }

    public function setCache(CacheInterface $cache, int $lifeTime = 0): void
    {
        $this->cache = $cache;
        $this->cacheLifeTime = $lifeTime;
    }

    public function getEntityCustomFields(string $entityName) : array
    {
        $cacheKey = UniversalCacheKeyGenerator::normalizeCacheKey($entityName);
        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($entityName) {
            if ($this->cacheLifeTime > 0) {
                $item->expiresAfter($this->cacheLifeTime);
            }
            return $this->getActualEntityCustomFields($entityName);
        });
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
