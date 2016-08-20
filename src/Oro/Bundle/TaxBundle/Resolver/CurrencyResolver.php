<?php

namespace Oro\Bundle\TaxBundle\Resolver;

use Oro\Bundle\TaxBundle\Model\AbstractResult;
use Oro\Bundle\TaxBundle\Model\AbstractResultElement;
use Oro\Bundle\TaxBundle\Model\Taxable;

class CurrencyResolver implements ResolverInterface
{
    /** {@inheritdoc} */
    public function resolve(Taxable $taxable)
    {
        $this->walk($taxable->getResult(), $taxable);

        foreach ($taxable->getItems() as $taxableItem) {
            $this->walk($taxableItem->getResult(), $taxable);
        }
    }

    /**
     * @param AbstractResult|array $result
     * @param Taxable $taxable
     */
    protected function walk($result, Taxable $taxable)
    {
        if ($result instanceof AbstractResultElement) {
            /** @var AbstractResultElement $resultElement */
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
