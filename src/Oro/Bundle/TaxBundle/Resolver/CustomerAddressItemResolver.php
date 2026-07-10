<?php

namespace Oro\Bundle\TaxBundle\Resolver;

use Brick\Math\BigDecimal;
use Oro\Bundle\TaxBundle\Model\Taxable;

/**
 * Resolves unit price and row total tax for a taxable item using its taxation address.
 */
class CustomerAddressItemResolver extends AbstractItemResolver
{
    /** {@inheritdoc} */
    public function resolve(Taxable $taxable)
    {
        if ($taxable->getItems()->count()) {
            return;
        }

        if (!$taxable->getPrice()->isPositive()) {
            return;
        }

        $address = $taxable->getTaxationAddress();
        if (!$address) {
            return;
        }

        if ($taxable->getResult()->isResultLocked()) {
            return;
        }

        $taxRules = $this->matcher->match($address, $this->getTaxCodes($taxable));
        $taxableAmount = BigDecimal::of($taxable->getPrice());

        $result = $taxable->getResult();
        $this->unitResolver->resolveUnitPrice($result, $taxRules, $taxableAmount);

        // When the row total is a final allocated amount (e.g. order-level discount), use it directly
        // to avoid rounding a synthetic per-unit price before multiplying it back by quantity.
        if ($taxable->getRowTotal() !== null) {
            $rowAmount = $taxable->getRowTotal();
            $quantity = BigDecimal::one();
        } else {
            $rowAmount = $taxableAmount;
            $quantity = $taxable->getQuantity();
        }
        $this->rowTotalResolver->resolveRowTotal($result, $taxRules, $rowAmount, $quantity);
        $result->lockResult();
    }
}
