<?php

namespace Oro\Bundle\ShippingBundle\Provider;

use Doctrine\Common\Collections\Collection;

/**
 * Represents a service that provide a set of methods that help working with grouped line items
 * in context of Multi Shipping functionality.
 */
interface GroupLineItemHelperInterface
{
    /**
     * @param Collection $lineItems
     * @param string     $groupingFieldPath
     *
     * @return array ['product.category:1' => [line item, ...], ...]
     */
    public function getGroupedLineItems(Collection $lineItems, string $groupingFieldPath): array;

    public function isLineItemsGroupedByOrganization(string $groupingFieldPath): bool;

    public function getGroupingFieldPath(): string;

    public function getGroupingFieldValue(object $lineItem, string $groupingFieldPath): mixed;

    public function getLineItemGroupKey(object $lineItem, string $groupingFieldPath): string;
}
