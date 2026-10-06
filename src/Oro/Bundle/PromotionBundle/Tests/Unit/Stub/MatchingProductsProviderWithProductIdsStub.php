<?php

declare(strict_types=1);

namespace Oro\Bundle\PromotionBundle\Tests\Unit\Stub;

use Oro\Bundle\OrganizationBundle\Entity\Organization;
use Oro\Bundle\PromotionBundle\Provider\MatchingProductsProviderInterface;
use Oro\Bundle\SegmentBundle\Entity\Segment;

/**
 * Represents a matching products provider that implements getMatchingProductIds().
 * MatchingProductsProviderInterface declares this method via an annotation to keep BC,
 * and such methods cannot be configured on a test double, so this stub declares it explicitly.
 */
interface MatchingProductsProviderWithProductIdsStub extends MatchingProductsProviderInterface
{
    /**
     * @param Segment $segment
     * @param array $lineItems
     * @param Organization|null $promotionOrganization
     * @return array<int>
     */
    public function getMatchingProductIds(
        Segment $segment,
        array $lineItems,
        ?Organization $promotionOrganization = null
    ): array;
}
