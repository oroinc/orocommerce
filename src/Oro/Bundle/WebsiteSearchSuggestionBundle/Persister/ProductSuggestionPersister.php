<?php

namespace Oro\Bundle\WebsiteSearchSuggestionBundle\Persister;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Entity\ProductSuggestion;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Entity\Repository\ProductSuggestionRepository;
use Oro\Bundle\WebsiteSearchSuggestionBundle\Event\ProductSuggestionPersistEvent;
use Symfony\Component\EventDispatcher\EventDispatcherInterface;

/**
 * Persists suggestions product relations to database
 */
class ProductSuggestionPersister
{
    public function __construct(
        private ManagerRegistry $doctrine,
        private EventDispatcherInterface $eventDispatcher
    ) {
    }

    /**
     * @param array<int, array<int>> $productsBySuggestions
     *  [
     *      1 => [ // Suggestion ID
     *          1, 2, 3 // Product IDs
     *      ],
     *     // ...
     *  ]
     *
     * @return void
     */
    public function persistProductSuggestions(array $productsBySuggestions): void
    {
        $insertedProductSuggestionIds = $this->getProductSuggestionRepository()
            ->insertProductSuggestions($productsBySuggestions);

        if (empty($insertedProductSuggestionIds)) {
            return;
        }

        $event = new ProductSuggestionPersistEvent();
        $event->setPersistedProductSuggestionIds($insertedProductSuggestionIds);

        $this->eventDispatcher->dispatch($event);
    }

    private function getProductSuggestionRepository(): ProductSuggestionRepository
    {
        return $this->doctrine->getRepository(ProductSuggestion::class);
    }
}
