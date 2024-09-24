<?php

namespace Oro\Bundle\PricingBundle\Debug\Provider;

/**
 * Provide information about price list assignments from all providers registered
 * under oro_pricing.debug.provider.price_list_assignment tag.
 *
 * @internal This service is applicable for pricing debug purpose only.
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

    #[\Override]
    public function getPriceListAssignments(): ?array
    {
        $assignments = [];

        foreach ($this->providers as $provider) {
            $levelAssignments = $provider->getPriceListAssignments();
            if ($levelAssignments) {
                $assignments[] = $levelAssignments;
            }
            if (!empty($levelAssignments['stop'])) {
                break;
            }
        }

        return $assignments;
    }
}
