<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Oro\Bundle\EntityBundle\Manager\PreloadingManager;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerInterface;
use Oro\Bundle\PlatformBundle\EventListener\OptionalListenerTrait;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;
use Oro\Bundle\WebsiteSearchBundle\Manager\WebsiteContextManager;

/**
 * Preloading products with required data at the start of website reindex operation.
 */
class WebsiteSearchProductPreloadingIndexerListener implements
    WebsiteSearchProductIndexerListenerInterface,
    OptionalListenerInterface
{
    use OptionalListenerTrait;

    private const PRODUCT_ATTRIBUTES_TO_PRELOAD = [
        'attributeFamily' => [],
        'names' => [],
        'descriptions' => [],
        'shortDescriptions' => [],
        'brand' => [],
        'metaDescriptions' => [],
        'metaKeywords' => [],
    ];

    private array $fieldsToPreload = [
        ...self::PRODUCT_ATTRIBUTES_TO_PRELOAD,
        'kitItems' => [
            'labels' => [],
            'kitItemProducts' => [
                'product' => [
                    ...self::PRODUCT_ATTRIBUTES_TO_PRELOAD,
                ],
            ],
        ]
    ];

    public function __construct(
        private WebsiteContextManager $websiteContextManager,
        private PreloadingManager $preloadingManager,
    ) {
    }

    public function setFieldsToPreload(array $fieldsToPreload): void
    {
        $this->fieldsToPreload = $fieldsToPreload;
    }

    public function onWebsiteSearchIndex(IndexEntityEvent $event): void
    {
        if (!$this->enabled) {
            return;
        }

        $websiteId = $this->websiteContextManager->getWebsiteId($event->getContext());
        if (!$websiteId) {
            $event->stopPropagation();
            return;
        }

        $this->preloadingManager->preloadInEntities($event->getEntities(), $this->fieldsToPreload);
    }
}
