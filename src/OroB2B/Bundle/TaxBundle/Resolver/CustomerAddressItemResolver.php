<?php

namespace OroB2B\Bundle\TaxBundle\Resolver;

use Brick\Math\BigDecimal;

use OroB2B\Bundle\TaxBundle\Model\Taxable;

class CustomerAddressItemResolver extends AbstractItemResolver
{
    /** {@inheritdoc} */
    public function resolve(Taxable $taxable)
    {
        if ($taxable->getItems()->count()) {
            return;
        }

        if (!$taxable->getPrice()) {
            return;
        }

        $address = $taxable->getDestination();
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
        $this->rowTotalResolver->resolveRowTotal($result, $taxRules, $taxableAmount, $taxable->getQuantity());
        $result->lockResult();
    }
}
