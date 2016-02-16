<?php

namespace OroB2B\Bundle\TaxBundle\Resolver\SellerResolver\VatResolver\EUVatResolver;

use Brick\Math\BigDecimal;

use OroB2B\Bundle\TaxBundle\Entity\ProductTaxCode;
use OroB2B\Bundle\TaxBundle\Matcher\CountryMatcher;
use OroB2B\Bundle\TaxBundle\Matcher\MatcherInterface;
use OroB2B\Bundle\TaxBundle\Model\Taxable;
use OroB2B\Bundle\TaxBundle\Model\TaxCode;
use OroB2B\Bundle\TaxBundle\Model\TaxCodes;
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

        $buyerAddress = $taxable->getDestination();
        if (!$buyerAddress) {
            return;
        }

        if ($taxable->getResult()->count() !== 0) {
            return;
        }

        $isBuyerFromEU = $this->countryMatcher->isEuropeanUnionCountry($taxable->getDestination()->getCountryIso2());

        if ($isBuyerFromEU && $taxable->getContextValue(Taxable::DIGITAL_PRODUCT)) {
            $taxRules = $this->countryMatcher->match($buyerAddress, $this->getTaxCodes($taxable));
            $taxableAmount = BigDecimal::of($taxable->getPrice());

            $result = $taxable->getResult();
            $this->unitResolver->resolveUnitPrice($result, $taxRules, $taxableAmount);
            $this->rowTotalResolver->resolveRowTotal($result, $taxRules, $taxableAmount, $taxable->getQuantity());
        }
    }

    /**
     * @param Taxable $taxable
     * @return TaxCodes
     */
    protected function getTaxCodes(Taxable $taxable)
    {
        return TaxCodes::create(
            [
                TaxCode::create(
                    ProductTaxCode::TYPE_PRODUCT,
                    $taxable->getContextValue(Taxable::PRODUCT_TAX_CODE)
                ),
            ]
        );
    }

    /**
     * @param MatcherInterface $matcher
     */
    public function setMatcher(MatcherInterface $matcher)
    {
        $this->countryMatcher = $matcher;
    }
}
