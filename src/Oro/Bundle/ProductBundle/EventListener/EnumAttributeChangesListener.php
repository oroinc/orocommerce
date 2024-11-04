<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Oro\Bundle\CacheBundle\Generator\UniversalCacheKeyGenerator;
use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Event\PostFlushConfigEvent;
use Oro\Bundle\EntityExtendBundle\Tools\ExtendHelper;
use Symfony\Contracts\Cache\CacheInterface;

/**
 * Watch changes of enum attributes made by the user from UI.
 */
class EnumAttributeChangesListener
{
    public function __construct(private CacheInterface $enumTypeCache)
    {
    }

    public function postFlush(PostFlushConfigEvent $event): void
    {
        $configManager = $event->getConfigManager();
        foreach ($event->getModels() as $model) {
            if (!$model instanceof FieldConfigModel) {
                continue;
            }
            $className = $model->getEntity()->getClassName();
            $config = $configManager->getProvider('attribute')->getConfig($className, $model->getFieldName());
            if ($config->is('is_attribute') && ExtendHelper::isEnumerableType($model->getType())) {
                $this->clearRelatedCache($className);
            }
        }
    }

    private function clearRelatedCache(string $entityName): void
    {
        $cacheKey = UniversalCacheKeyGenerator::normalizeCacheKey($entityName);

        $this->enumTypeCache->delete($cacheKey);
    }
}
