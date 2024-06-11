<?php

namespace Oro\Bundle\ProductBundle\EventListener\WebsiteSearchTerm\ProductCollection;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Provider\WebsiteSearchTerm\SearchTermProductCollectionSegmentsProvider;
use Oro\Bundle\SegmentBundle\Entity\Manager\StaticSegmentManager;
use Oro\Bundle\WebsiteSearchBundle\Engine\AbstractIndexer;
use Oro\Bundle\WebsiteSearchBundle\Engine\Context\ContextTrait;
use Oro\Bundle\WebsiteSearchBundle\Event\BeforeReindexEvent;
use Oro\Bundle\WebsiteSearchTermBundle\Entity\SearchTerm;

/**
 * Refreshes segments referenced by {@see SearchTerm} entities.
 */
class ProductCollectionSearchTermBeforeReindexEventListener
{
    use ContextTrait;

    public function __construct(
        private readonly StaticSegmentManager $staticSegmentManager,
        private readonly SearchTermProductCollectionSegmentsProvider $searchTermProductCollectionSegmentsProvider
    ) {
    }

    public function onBeforeReindex(BeforeReindexEvent $event): void
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

        $entityIds = $event->getContext()[AbstractIndexer::CONTEXT_ENTITIES_IDS_KEY] ?? [];
        $websiteIds = $this->getContextWebsiteIds($event->getContext());
        if ($websiteIds) {
            foreach ($websiteIds as $websiteId) {
                $segments = $this->searchTermProductCollectionSegmentsProvider
                    ->getSearchTermProductCollectionSegments($websiteId);
                $this->refreshSegments($segments, $entityIds);
            }
        } else {
            $this->refreshSegments(
                $this->searchTermProductCollectionSegmentsProvider->getSearchTermProductCollectionSegments(),
                $entityIds
            );
        }
    }

    private function refreshSegments(iterable $segments, array $entityIds): void
    {
        foreach ($segments as $segment) {
            $this->staticSegmentManager->run($segment, $entityIds);
        }
    }
}
