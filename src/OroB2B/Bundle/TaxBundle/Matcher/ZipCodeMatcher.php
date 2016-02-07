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
    public function match(AbstractAddress $address, $productTaxCode, $accountTaxCode)
    {
        $region = $address->getRegion();
        $regionText = $address->getRegionText();
        $country = $address->getCountry();

        $regionTaxRules = $this->regionMatcher->match($address, $productTaxCode, $accountTaxCode);

        if (null === $productTaxCode ||
            null === $accountTaxCode ||
            null === $country ||
            (null === $region && empty($regionText))
        ) {
            return $regionTaxRules;
        }

        $zipCodeTaxRules = $this->getTaxRuleRepository()->findByZipCodeAndProductTaxCodeAndAccountTaxCode(
            $productTaxCode,
            $accountTaxCode,
            $address->getPostalCode(),
            $country,
            $region,
            $regionText
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
