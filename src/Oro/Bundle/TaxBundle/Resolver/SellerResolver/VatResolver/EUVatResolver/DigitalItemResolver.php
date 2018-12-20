<?php

namespace Oro\Bundle\TaxBundle\Resolver\SellerResolver\VatResolver\EUVatResolver;

use Brick\Math\BigDecimal;
use Oro\Bundle\TaxBundle\Matcher\EuropeanUnionHelper;
use Oro\Bundle\TaxBundle\Model\Taxable;
use Oro\Bundle\TaxBundle\Resolver\AbstractItemResolver;

/**
 * Resolver to switch taxation address to a customer's one for digital products
 */
class DigitalItemResolver extends AbstractItemResolver
{
    /**
     * @param Taxable $taxable
     */
    public function resolve(Taxable $taxable)
    {
        if ($taxable->getItems()->count() !== 0) {
            return;
        }

        if (!$taxable->getPrice()->isPositive()) {
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
            $taxable->makeDestinationAddressTaxable();

            $taxRules = $this->matcher->match($buyerAddress, $this->getTaxCodes($taxable));
            $taxableAmount = BigDecimal::of($taxable->getPrice());

            $result = $taxable->getResult();
            $this->unitResolver->resolveUnitPrice($result, $taxRules, $taxableAmount);
            $this->rowTotalResolver->resolveRowTotal($result, $taxRules, $taxableAmount, $taxable->getQuantity());

            $result->lockResult();
        }
    }
}
