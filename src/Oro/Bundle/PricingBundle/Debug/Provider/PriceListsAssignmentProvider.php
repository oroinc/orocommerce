<?php

namespace Oro\Bundle\PricingBundle\Debug\Provider;

/**
 * Provide information about price list assignments from all providers registered
 * under oro_pricing.debug.provider.price_list_assignment tag.
 */
class PriceListsAssignmentProvider implements PriceListsAssignmentProviderInterface
{
    /**
     * @var iterable|PriceListsAssignmentProviderInterface[]
     */
    private iterable $providers;

    public function __construct(iterable $providers)
    {
        $this->providers = $providers;
    }

    public function getPriceListAssignments(): ?array
    {
        $assignments = [];

        foreach ($this->providers as $provider) {
            $levelAssignments = $provider->getPriceListAssignments();
            if ($levelAssignments && !empty($levelAssignments['priceLists'])) {
                $assignments[] = $levelAssignments;
            }
            if (!empty($levelAssignments['stop'])) {
                break;
            }
        }

        return $assignments;
    }
}
