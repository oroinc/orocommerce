<?php

namespace Oro\Bundle\TaxBundle\Matcher;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;
use Oro\Bundle\TaxBundle\Model\TaxCodes;
use Oro\Bundle\TaxBundle\Provider\AddressResolverSettingsProvider;

/**
 * Uses registered matchers to finds TaxRules by address.
 */
class ResolvableMatcher implements MatcherInterface
{
    /** @var AddressMatcherRegistry */
    protected $addressMatcherRegistry;

    /** @var AddressResolverSettingsProvider */
    protected $settingsProvider;

    /**
     * @param AddressMatcherRegistry          $addressMatcherRegistry
     * @param AddressResolverSettingsProvider $taxationSettingsProvider
     */
    public function __construct(
        AddressMatcherRegistry $addressMatcherRegistry,
        AddressResolverSettingsProvider $taxationSettingsProvider
    ) {
        $this->addressMatcherRegistry = $addressMatcherRegistry;
        $this->settingsProvider = $taxationSettingsProvider;
    }

    /**
     * {@inheritdoc}
     */
    public function match(AbstractAddress $address, TaxCodes $taxCodes)
    {
        return $this->getMatcherFromRegistry()->match($address, $taxCodes);
    }

    /**
     * @return MatcherInterface
     */
    private function getMatcherFromRegistry()
    {
        return $this->addressMatcherRegistry->getMatcherByType(
            $this->settingsProvider->getAddressResolverGranularity()
        );
    }
}
