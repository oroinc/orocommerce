<?php

declare(strict_types=1);

namespace Oro\Bundle\ProductBundle\Service;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\ProductBundle\Entity\CollectionSortOrder;
use Oro\Bundle\ProductBundle\Entity\Repository\CollectionSortOrderRepository;
use Oro\Bundle\ProductBundle\Service\ProductCollectionDefinitionConverter as Converter;
use Oro\Bundle\SegmentBundle\Entity\Segment;

/**
 * Manages manually included/excluded products in the product collection segment.
 * Removes {@see CollectionSortOrder} entities for excluded products.
 */
class ProductCollectionSegmentManipulator
{
    private ManagerRegistry $managerRegistry;

    private Converter $definitionConverter;

    public function __construct(
        ManagerRegistry $managerRegistry,
        Converter $definitionConverter
    ) {
        $this->managerRegistry = $managerRegistry;
        $this->definitionConverter = $definitionConverter;
    }

    /**
     * @param Segment $segment
     * @param int[] $appendProductIds
     * @param int[] $removeProductIds
     *
     * @return array<int[],int[]> Resulting included and excluded product ids
     *  [
     *      [10, 20, 30], // included products ids
     *      [40, 50], // excluded products ids
     *  ]
     */
    public function updateManuallyManagedProducts(
        Segment $segment,
        array $appendProductIds,
        array $removeProductIds
    ): array {
        $definitionParts = $this->definitionConverter->getDefinitionParts($segment->getDefinition());

        $excludedProductsIds = $this->getIdsFromString($definitionParts[Converter::EXCLUDED_FILTER_KEY] ?? '');
        $includedProductsIds = $this->getIdsFromString($definitionParts[Converter::INCLUDED_FILTER_KEY] ?? '');

        $includedProductsIds = array_merge($includedProductsIds, $appendProductIds);
        $excludedProductsIds = array_diff($excludedProductsIds, $appendProductIds);

        $excludedProductsIds = array_merge($excludedProductsIds, $removeProductIds);
        $includedProductsIds = array_diff($includedProductsIds, $removeProductIds);

        $excludedProductsIds = array_values(array_unique($excludedProductsIds));
        $includedProductsIds = array_values(array_unique($includedProductsIds));

        $updatedRawDefinition = $this->definitionConverter->putConditionsInDefinition(
            $definitionParts[Converter::DEFINITION_KEY],
            implode(',', $excludedProductsIds),
            implode(',', $includedProductsIds)
        );

        $segment->setDefinition($updatedRawDefinition);

        /** @var CollectionSortOrderRepository $sortOrderRepo */
        $sortOrderRepo = $this->managerRegistry->getRepository(CollectionSortOrder::class);
        $sortOrderRepo->removeBySegmentAndProductIds($segment->getId(), $excludedProductsIds);

        return [$includedProductsIds, $excludedProductsIds];
    }

    private function getIdsFromString(string $ids): array
    {
        return array_filter(explode(',', $ids));
    }
}
