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

    /** @var MatcherInterface */
    protected $matcher;

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
        if (!$this->matcher) {
            $this->matcher = $this->addressMatcherRegistry->getMatcherByType(
                $this->settingsProvider->getAddressResolverGranularity()
            );
        }

        return $this->matcher;
    }
}
