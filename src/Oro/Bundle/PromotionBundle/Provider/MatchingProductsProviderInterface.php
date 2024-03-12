<?php

namespace Oro\Bundle\PromotionBundle\Provider;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\SegmentBundle\Entity\Segment;

/**
 * Interface for provider that returns products from line items which fit segment's conditions.
 */
interface MatchingProductsProviderInterface
{
    public function hasMatchingProducts(Segment $segment, array $lineItems): bool;
    public function getMatchingProducts(
        Segment $segment,
        array $lineItems,
        ?Organization $promotionOrganization = null
    ): array;
}
