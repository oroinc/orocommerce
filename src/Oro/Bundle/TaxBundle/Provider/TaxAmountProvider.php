<?php

namespace Oro\Bundle\TaxBundle\Provider;

use Oro\Bundle\TaxBundle\Exception\TaxationDisabledException;

/**
 * Provides tax amount for entity.
 */
class TaxAmountProvider
{
    private TaxProviderRegistry $taxProviderRegistry;

    private TaxationSettingsProvider $taxationSettingsProvider;

    public function __construct(TaxProviderRegistry $taxProviderRegistry)
    {
        $this->taxProviderRegistry = $taxProviderRegistry;
    }

    public function setTaxationSettingsProvider(TaxationSettingsProvider $taxationSettingsProvider): void
    {
        $this->taxationSettingsProvider = $taxationSettingsProvider;
    }

    /**
     * Gets tax amount for entity.
     *
     * @throws TaxationDisabledException
     */
    public function getTaxAmount(object $entity): float
    {
        $provider = $this->taxProviderRegistry->getEnabledProvider();
        $result = $provider->loadTax($entity);
        $taxAmount = (float)$result->getTotal()->getTaxAmount();

        return $this->prepareResult($taxAmount);
    }

    /**
     * Gets excluded tax amount for entity. It is usually used between external transactions.
     *
     * @throws TaxationDisabledException
     */
    public function getExcludedTaxAmount(object $entity): float
    {
        $provider = $this->taxProviderRegistry->getEnabledProvider();
        $tax = $provider->loadTax($entity);
        $shippingTax = (float)$tax->getShipping()->getTaxAmount();
        $productTax = (float)$tax->getTotal()->getTaxAmount() - $shippingTax;
        $taxAmount = ($this->taxationSettingsProvider->isProductPricesIncludeTax() ? 0 : $productTax)
            + ($this->taxationSettingsProvider->isShippingRatesIncludeTax() ? 0 : $shippingTax);

        return $this->prepareResult($taxAmount);
    }

    public function isTotalIncludedTax(): bool
    {
        if ($this->taxationSettingsProvider->isProductPricesIncludeTax()
            && $this->taxationSettingsProvider->isShippingRatesIncludeTax()) {
            return true;
        }

        return false;
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
