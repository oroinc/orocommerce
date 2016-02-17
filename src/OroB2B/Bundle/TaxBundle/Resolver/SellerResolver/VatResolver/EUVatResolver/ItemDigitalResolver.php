<?php

namespace OroB2B\Bundle\TaxBundle\Resolver\SellerResolver\VatResolver\EUVatResolver;

use Brick\Math\BigDecimal;

use OroB2B\Bundle\TaxBundle\Matcher\EuropeanUnionHelper;
use OroB2B\Bundle\TaxBundle\Model\Taxable;
use OroB2B\Bundle\TaxBundle\Model\TaxCode;
use OroB2B\Bundle\TaxBundle\Model\TaxCodes;
use OroB2B\Bundle\TaxBundle\Resolver\AbstractItemResolver;

class ItemDigitalResolver extends AbstractItemResolver
{
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

        if ($taxable->getResult()->isResultLocked()) {
            return;
        }

        $isBuyerFromEU = EuropeanUnionHelper::isEuropeanUnionCountry($taxable->getDestination()->getCountryIso2());

        if ($isBuyerFromEU && $taxable->getContextValue(Taxable::DIGITAL_PRODUCT)) {
            $taxRules = $this->matcher->match($buyerAddress, $this->getTaxCodes($taxable));
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
        $taxCodes = [];

        $productContextCode = $taxable->getContextValue(Taxable::PRODUCT_TAX_CODE);
        if (null !== $productContextCode) {
            $taxCodes[] = TaxCode::create($productContextCode, TaxCode::TYPE_PRODUCT);
        }

        return TaxCodes::create($taxCodes);
    }
}
