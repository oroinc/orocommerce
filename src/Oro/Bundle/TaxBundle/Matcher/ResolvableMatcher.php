<?php

namespace Oro\Bundle\TaxBundle\Matcher;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\TaxBundle\Model\TaxCodes;
use Oro\Bundle\TaxBundle\Provider\AddressResolverSettingsProvider;

/**
 * Uses registered matchers to finds tax rules by an address.
 */
class ResolvableMatcher implements MatcherInterface
{
    private AddressMatcherRegistry $addressMatcherRegistry;
    private AddressResolverSettingsProvider $settingsProvider;
    private ?MatcherInterface $matcher = null;

    public function __construct(
        AddressMatcherRegistry $addressMatcherRegistry,
        AddressResolverSettingsProvider $taxationSettingsProvider
    ) {
        $this->addressMatcherRegistry = $addressMatcherRegistry;
        $this->settingsProvider = $taxationSettingsProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function match(AbstractAddress $address, TaxCodes $taxCodes): array
    {
        if (null === $this->matcher) {
            $this->matcher = $this->addressMatcherRegistry->getMatcherByType(
                $this->settingsProvider->getAddressResolverGranularity()
            );
        }

        return $this->matcher->match($address, $taxCodes);
    }
}
