<?php

namespace OroB2B\Bundle\TaxBundle\Resolver\SellerResolver\VatResolver\EUVatResolver;

use OroB2B\Bundle\TaxBundle\Matcher\CountryMatcher;
use OroB2B\Bundle\TaxBundle\Model\Taxable;
use OroB2B\Bundle\TaxBundle\Resolver\ResolverInterface;

class DigitalResolver implements ResolverInterface
{
    /**
     * @var ResolverInterface
     */
    protected $resolver;

    /**
     * @var CountryMatcher
     */
    protected $countryMatcher;

    /**
     * @param CountryMatcher    $countryMatcher
     * @param ResolverInterface $resolver
     */
    public function __construct(CountryMatcher $countryMatcher, ResolverInterface $resolver)
    {
        $this->countryMatcher = $countryMatcher;
        $this->resolver = $resolver;
    }

    /**
     * @param Taxable $taxable
     */
    public function resolve(Taxable $taxable)
    {
        if ($taxable->getItems()->count() === 0) {
            return;
        }

        $sellerAddress = $taxable->getOrigin();
        if (!$sellerAddress) {
            return;
        }

        $buyerAddress = $taxable->getDestination();
        if (!$buyerAddress) {
            return;
        }

        $isSellerFromEU = $this->countryMatcher->isEuropeanUnionCountry($taxable->getOrigin()->getCountryIso2());
        $isBuyerFromEU = $this->countryMatcher->isEuropeanUnionCountry($taxable->getDestination()->getCountryIso2());

        if (!$isSellerFromEU && $isBuyerFromEU) {
            foreach ($taxable->getItems() as $item) {
                if ($item->getResult()->getTotal()->count() === 0) {
                    $this->resolver->resolve($item);
                }
            }
        }
    }
}
