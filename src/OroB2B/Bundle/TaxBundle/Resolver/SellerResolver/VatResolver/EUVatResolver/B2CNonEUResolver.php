<?php

namespace OroB2B\Bundle\TaxBundle\Resolver\SellerResolver\VatResolver\EUVatResolver;

use OroB2B\Bundle\TaxBundle\Matcher\CountryMatcher;
use OroB2B\Bundle\TaxBundle\Model\Taxable;
use OroB2B\Bundle\TaxBundle\Resolver\ResolverInterface;
use OroB2B\Bundle\TaxBundle\Resolver\StopPropagationException;

class B2CNonEUResolver implements ResolverInterface
{
    /**
     * @var CountryMatcher
     */
    protected $countryMatcher;

    /**
     * @var ResolverInterface
     */
    protected $resolver;

    /**
     * @param CountryMatcher    $countryMatcher
     */
    public function __construct(CountryMatcher $countryMatcher)
    {
        $this->countryMatcher = $countryMatcher;
    }

    /**
     * {@inheritdoc}
     */
    public function resolve(Taxable $taxable)
    {
        $isSellerFromEU = $this->countryMatcher->isEuropeanUnionCountry($taxable->getOrigin()->getCountryIso2());
        $isBuyerFromEU = $this->countryMatcher->isEuropeanUnionCountry($taxable->getDestination()->getCountryIso2());

        if ($isSellerFromEU && !$isBuyerFromEU) {
            throw new StopPropagationException();
        }
    }
}
