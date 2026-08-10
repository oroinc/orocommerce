<?php

namespace Oro\Bundle\PromotionBundle\Provider;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\SegmentBundle\Entity\Segment;

/**
 * Interface for provider that returns products from line items which fit segment's conditions.
 */
interface MatchingProductsProviderInterface
{
    public function hasMatchingProducts(Segment $segment, array $lineItems): bool;

    /**
     * @param Segment $segment
     * @param array $lineItems
     * @param Organization|null $promotionOrganization
     * @return array<Product>
     */
    public function getMatchingProducts(
        Segment $segment,
        array $lineItems,
        ?Organization $promotionOrganization = null
    ): array;

    /**
     * @param Segment $segment
     * @param array $lineItems
     * @param Organization|null $promotionOrganization
     *
     * @return array<int>
     */
    public function getMatchingProductIds(
        Segment $segment,
        array $lineItems,
        ?Organization $promotionOrganization = null
    ): array;
}
