<?php

namespace Oro\Bundle\TaxBundle\Resolver\CustomerAddress;

use Oro\Bundle\TaxBundle\Model\Taxable;
use Oro\Bundle\TaxBundle\Resolver\AbstractItemResolver;

/**
 * Resolve the unit price and row total for Taxable related to OrderProductKitItemLineItem.
 */
class CustomerAddressKitItemResolver extends AbstractItemResolver
{
    #[\Override]
    public function resolve(Taxable $taxable): void
    {
        if (!$this->isApplicable($taxable)) {
            return;
        }

        $this->calculateUnitPriceAndRowTotal($taxable);
    }

    private function isApplicable(Taxable $taxable): bool
    {
        return $taxable->getPrice()->isPositive() &&
            $taxable->getTaxationAddress() &&
            !$taxable->getResult()->isResultLocked();
    }
}
