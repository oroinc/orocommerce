<?php

namespace OroB2B\Bundle\TaxBundle\Matcher;

use Oro\Bundle\AddressBundle\Entity\AbstractAddress;

class ZipCodeMatcher extends AbstractMatcher
{
    /**
     * @var MatcherInterface
     */
    protected $regionMatcher;

    /**
     * {@inheritdoc}
     */
    public function match(AbstractAddress $address)
    {
        $regionText = $address->getRegion() ? null : $address->getRegionText();
        $country = $address->getRegion() ? null : $address->getCountry();

        $regionTaxRules = $this->regionMatcher->match($address);
        $zipCodeTaxRules = $this->getTaxRuleRepository()->findByZipCode(
            $address->getPostalCode(),
            $address->getRegion(),
            $regionText,
            $country
        );

        return $this->mergeResult($regionTaxRules, $zipCodeTaxRules);
    }

    /**
     * @param MatcherInterface $regionMatcher
     */
    public function setRegionMatcher(MatcherInterface $regionMatcher)
    {
        $this->regionMatcher = $regionMatcher;
    }
}
