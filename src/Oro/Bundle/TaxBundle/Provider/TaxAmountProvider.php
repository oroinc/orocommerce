<?php

namespace Oro\Bundle\TaxBundle\Provider;

use Oro\Bundle\TaxBundle\Exception\TaxationDisabledException;

/**
 * Provides tax amount for entity.
 */
class TaxAmountProvider
{
    /**
     * @var TaxProviderRegistry
     */
    private $taxProviderRegistry;

    public function __construct(TaxProviderRegistry $taxProviderRegistry)
    {
        $this->taxProviderRegistry = $taxProviderRegistry;
    }

    /**
     * Gets tax amount for entity.
     *
     * @param object $entity
     * @return float
     * @throws TaxationDisabledException
     */
    public function getTaxAmount($entity): float
    {
        $provider = $this->taxProviderRegistry->getEnabledProvider();
        $result = $provider->loadTax($entity);
        $taxAmount = (float) $result->getTotal()->getTaxAmount();

        return $this->prepareResult($taxAmount);
    }

    /**
     * Prepares result.
     */
    private function prepareResult(float $taxAmount): float
    {
        $isTooSmall = abs($taxAmount) <= 1e-6;

        return $isTooSmall ? 0.0 : $taxAmount;
    }
}
