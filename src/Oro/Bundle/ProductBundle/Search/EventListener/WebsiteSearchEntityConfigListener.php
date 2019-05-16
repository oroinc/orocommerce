<?php

namespace Oro\Bundle\ProductBundle\Search\EventListener;

use Oro\Bundle\EntityConfigBundle\Entity\FieldConfigModel;
use Oro\Bundle\EntityConfigBundle\Event\PostFlushConfigEvent;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\WebsiteSearchBundle\Provider\WebsiteSearchMappingProvider;

/**
 * Clears mapping provider's cache if attribute's field config has changed.
 *
 * @deprecated will be remove in 4.0
 */
class WebsiteSearchEntityConfigListener
{
    /** @var WebsiteSearchMappingProvider */
    protected $mappingProvider;

    /**
     * @param WebsiteSearchMappingProvider $mappingProvider
     */
    public function __construct(WebsiteSearchMappingProvider $mappingProvider)
    {
        $this->mappingProvider = $mappingProvider;
    }

    /**
     * @param PostFlushConfigEvent $event
     */
    public function postFlush(PostFlushConfigEvent $event)
    {
        $configManager = $event->getConfigManager();

        foreach ($event->getModels() as $model) {
            if (!$model instanceof FieldConfigModel) {
                continue;
            }

            $className = $model->getEntity()->getClassName();

            if (!is_a($className, Product::class, true)) {
                continue;
            }

            $config = $configManager->getProvider('attribute')->getConfig($className, $model->getFieldName());

            if ($config->is('is_attribute')) {
                $this->mappingProvider->clearCache();
                break;
            }
        }
    }
}
