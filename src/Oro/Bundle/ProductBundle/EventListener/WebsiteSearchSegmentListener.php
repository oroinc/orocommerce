<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Provider\ContentVariantSegmentProvider;
use Oro\Bundle\SegmentBundle\Entity\Manager\StaticSegmentManager;
use Oro\Bundle\WebsiteSearchBundle\Engine\Context\ContextTrait;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;

/**
 * Website search index event listener that updates snapshot for segments which are used to form product collections and
 * therefore are related to content variants. Needs to be executed before WebsiteSearchProductIndexerListener listener.
 */
class WebsiteSearchSegmentListener
{
    use ContextTrait;

    /**
     * @var ContentVariantSegmentProvider
     */
    private $contentVariantSegmentProvider;

    /**
     * @var StaticSegmentManager
     */
    private $staticSegmentManager;

    /**
     * @param ContentVariantSegmentProvider $contentVariantSegmentProvider
     * @param StaticSegmentManager $staticSegmentManager
     */
    public function __construct(
        ContentVariantSegmentProvider $contentVariantSegmentProvider,
        StaticSegmentManager $staticSegmentManager
    ) {
        $this->contentVariantSegmentProvider = $contentVariantSegmentProvider;
        $this->staticSegmentManager = $staticSegmentManager;
    }

    /**
     * @param IndexEntityEvent $event
     */
    public function onWebsiteSearchIndex(IndexEntityEvent $event)
    {
        // entity check is done inside the listener intentionally because common event has to be used instead of
        // product specific event to make sure that this listener will be executed
        // before the Oro\Bundle\WebCatalogBundle\EventListener\WebCatalogEntityIndexerListener
        if ($event->getEntityClass() !== Product::class) {
            return;
        }

        $websiteId = $this->getContextCurrentWebsiteId($event->getContext());
        if ($websiteId) {
            $segments = $this->contentVariantSegmentProvider->getContentVariantSegmentsByWebsiteId($websiteId);
        } else {
            $segments = $this->contentVariantSegmentProvider->getContentVariantSegments();
        }
        foreach ($segments as $segment) {
            $this->staticSegmentManager->run($segment, $this->getEntityIds($event));
        }
    }

    /**
     * @param IndexEntityEvent $event
     * @return array
     */
    private function getEntityIds(IndexEntityEvent $event): array
    {
        return array_map(
            function (Product $product) {
                return $product->getId();
            },
            $event->getEntities()
        );
    }
}
