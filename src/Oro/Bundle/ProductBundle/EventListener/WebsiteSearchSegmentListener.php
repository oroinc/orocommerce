<?php

namespace Oro\Bundle\ProductBundle\EventListener;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Provider\ContentVariantSegmentProvider;
use Oro\Bundle\SegmentBundle\Entity\Manager\StaticSegmentManager;
use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractIndexer;
use Oro\Bundle\WebsiteSearchBundle\Engine\Context\ContextTrait;
use Oro\Bundle\WebsiteSearchBundle\Event\BeforeReindexEvent;

/**
 * Website search index event listener that updates snapshot for segments which are used to form product collections and
 * therefore are related to content variants.
 * Needs to be executed before WebCatalogEntityIndexerListener and ManuallyAddedProductCollectionIndexerListener.
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

    public function __construct(
        ContentVariantSegmentProvider $contentVariantSegmentProvider,
        StaticSegmentManager $staticSegmentManager
    ) {
        $this->contentVariantSegmentProvider = $contentVariantSegmentProvider;
        $this->staticSegmentManager = $staticSegmentManager;
    }

    public function process(BeforeReindexEvent $event)
    {
        if (!$this->hasContextFieldGroup($event->getContext(), 'main')) {
            return;
        }

        $classes = \is_array($event->getClassOrClasses())
            ? $event->getClassOrClasses()
            : (array) $event->getClassOrClasses();
        if ($classes && !\in_array(Product::class, $classes, true)) {
            return;
        }

        $ids = $event->getContext()[AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY] ?? [];
        $websiteIds = $this->getContextWebsiteIds($event->getContext());
        if ($websiteIds) {
            foreach ($websiteIds as $websiteId) {
                $this->runSegmentActualization(
                    $this->contentVariantSegmentProvider->getContentVariantSegmentsByWebsiteId($websiteId),
                    $ids
                );
            }
        } else {
            $this->runSegmentActualization(
                $this->contentVariantSegmentProvider->getContentVariantSegments(),
                $ids
            );
        }
    }

    private function runSegmentActualization(iterable $segments, array $ids)
    {
        foreach ($segments as $segment) {
            $this->staticSegmentManager->run($segment, $ids);
        }
    }
}
