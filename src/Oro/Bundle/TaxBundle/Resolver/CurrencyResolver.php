<?php

namespace Oro\Bundle\TaxBundle\Resolver;

use Oro\Bundle\TaxBundle\Model\AbstractResult;
use Oro\Bundle\TaxBundle\Model\AbstractResultElement;
use Oro\Bundle\TaxBundle\Model\Taxable;

/**
 * Update currency for Taxable.
 */
class CurrencyResolver implements ResolverInterface
{
    #[\Override]
    public function resolve(Taxable $taxable): void
    {
        $this->walk($taxable->getResult(), $taxable);

        foreach ($taxable->getItems() as $taxableItem) {
            $this->walk($taxableItem->getResult(), $taxable);

            if ($taxableItem->isKitTaxable()) {
                foreach ($taxableItem->getItems() as $taxableKitItem) {
                    $this->walk($taxableKitItem->getResult(), $taxableItem);
                }
            }
        }
    }

    /**
     * @param AbstractResult|array $result
     */
    protected function walk($result, Taxable $taxable): void
    {
        if ($result instanceof AbstractResultElement) {
            $resultElement = $result;
            if (!$resultElement->getCurrency()) {
                $resultElement->setCurrency($taxable->getCurrency());
            }
        }

        if (is_array($result) || $result instanceof \Traversable) {
            foreach ($result as $resultItem) {
                $this->walk($resultItem, $taxable);
            }
        }
    }
}
