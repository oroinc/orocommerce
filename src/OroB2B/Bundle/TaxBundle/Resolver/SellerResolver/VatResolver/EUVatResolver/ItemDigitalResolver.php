<?php

namespace OroB2B\Bundle\TaxBundle\Resolver\SellerResolver\VatResolver\EUVatResolver;

use Brick\Math\BigDecimal;

use OroB2B\Bundle\TaxBundle\Matcher\CountryMatcher;
use OroB2B\Bundle\TaxBundle\Matcher\MatcherInterface;
use OroB2B\Bundle\TaxBundle\Model\Taxable;
use OroB2B\Bundle\TaxBundle\Resolver\AbstractItemResolver;

class ItemDigitalResolver extends AbstractItemResolver
{
    /**
     * @var CountryMatcher
     */
    protected $countryMatcher;

    /**
     * @param Taxable $taxable
     */
    public function resolve(Taxable $taxable)
    {
        if ($taxable->getItems()->count() !== 0) {
            return;
        }

        if (!$taxable->getPrice()) {
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

        if (!$isSellerFromEU && $isBuyerFromEU && $taxable->getContextValue(Taxable::DIGITAL_PRODUCT)) {
            if ($taxable->getResult()->getTotal()->count() === 0) {
                $taxRules = $this->countryMatcher->match(
                    $buyerAddress,
                    $taxable->getContextValue(Taxable::PRODUCT_TAX_CODE)
                );

                $taxableUnitPrice = BigDecimal::of($taxable->getPrice());
                $taxableAmount = $taxableUnitPrice->multipliedBy($taxable->getQuantity());

                $result = $taxable->getResult();
                $this->unitResolver->resolveUnitPrice($result, $taxRules, $taxableUnitPrice);
                $this->rowTotalResolver->resolveRowTotal($result, $taxRules, $taxableAmount);
            }
        }
    }

    /**
     * @param MatcherInterface $matcher
     */
    public function setMatcher(MatcherInterface $matcher)
    {
        $this->countryMatcher = $matcher;
    }
}
