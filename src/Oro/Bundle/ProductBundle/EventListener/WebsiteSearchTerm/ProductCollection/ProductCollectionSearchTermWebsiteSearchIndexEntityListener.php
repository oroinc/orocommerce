<?php

namespace Oro\Bundle\ProductBundle\EventListener\WebsiteSearchTerm\ProductCollection;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Provider\WebsiteSearchTerm\SearchTermsIndexDataProvider;
use Oro\Bundle\WebsiteSearchBundle\Engine\Context\ContextTrait;
use Oro\Bundle\WebsiteSearchBundle\Event\IndexEntityEvent;
use Oro\Bundle\WebsiteSearchBundle\Manager\WebsiteContextManager;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\AssignIdPlaceholder;
use Oro\Bundle\WebsiteSearchBundle\Placeholder\AssignTypePlaceholder;

/**
 * Adds the ASSIGN_TYPE_ASSIGN_ID field for products referenced in product collection segment to website search index.
 */
class ProductCollectionSearchTermWebsiteSearchIndexEntityListener
{
    use ContextTrait;

    public const ASSIGN_TYPE_SEARCH_TERM = 'search_term';

    public function __construct(
        private readonly SearchTermsIndexDataProvider $searchTermsIndexDataProvider,
        private readonly WebsiteContextManager $websiteContextManager
    ) {
    }

    public function onWebsiteSearchIndex(IndexEntityEvent $event): void
    {
        if (!$this->hasContextFieldGroup($event->getContext(), 'main')) {
            return;
        }

        if ($event->getEntityClass() !== Product::class) {
            return;
        }

        $searchTermsData = $this->searchTermsIndexDataProvider->getSearchTermsDataForProducts($event->getEntities());
        if (!$searchTermsData) {
            return;
        }

        foreach ($searchTermsData as $searchTermData) {
            if (!isset($searchTermData['searchTermId']) ||
                !isset($searchTermData['productCollectionSegmentId']) ||
                !isset($searchTermData['productCollectionProductId'])) {
                continue;
            }

            $event->addPlaceholderField(
                $searchTermData['productCollectionProductId'],
                'assigned_to.ASSIGN_TYPE_ASSIGN_ID',
                $searchTermData['productCollectionSegmentId'],
                [
                    AssignTypePlaceholder::NAME => self::ASSIGN_TYPE_SEARCH_TERM,
                    AssignIdPlaceholder::NAME => $searchTermData['searchTermId'],
                ]
            );
        }
    }
}
