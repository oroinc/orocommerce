<?php

namespace Oro\Bundle\TaxBundle\Matcher;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\TaxBundle\Model\TaxCodes;
use Oro\Bundle\TaxBundle\Provider\AddressResolverSettingsProvider;

class ResolvableMatcher implements MatcherInterface
{
    /** @var AddressMatcherRegistryInterface */
    protected $addressMatcherRegistry;

    /** @var AddressResolverSettingsProvider */
    protected $settingsProvider;

    /**
     * @param AddressMatcherRegistryInterface $addressMatcherRegistry
     * @param AddressResolverSettingsProvider $taxationSettingsProvider
     */
    public function __construct(
        AddressMatcherRegistryInterface $addressMatcherRegistry,
        AddressResolverSettingsProvider $taxationSettingsProvider
    ) {
        $this->addressMatcherRegistry = $addressMatcherRegistry;
        $this->settingsProvider = $taxationSettingsProvider;
    }

    /**
     * @return string
     */
    private function getGranularity()
    {
        return $this->settingsProvider->getAddressResolverGranularity();
    }

    /**
     * {@inheritdoc}
     */
    public function match(AbstractAddress $address, TaxCodes $taxCodes)
    {
        $configuredMatcher = $this->getMatcherFromRegistry();

        return $configuredMatcher->match($address, $taxCodes);
    }

    /**
     * @return MatcherInterface
     */
    private function getMatcherFromRegistry()
    {
        return $this->addressMatcherRegistry->getMatcherByType($this->getGranularity());
    }
}
