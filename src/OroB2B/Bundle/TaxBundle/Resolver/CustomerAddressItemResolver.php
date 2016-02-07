<?php

namespace OroB2B\Bundle\TaxBundle\Resolver;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

use OroB2B\Bundle\TaxBundle\Calculator\TaxCalculatorInterface;
use OroB2B\Bundle\TaxBundle\Entity\TaxRule;
use OroB2B\Bundle\TaxBundle\Model\Result;
use OroB2B\Bundle\TaxBundle\Model\Taxable;
use OroB2B\Bundle\TaxBundle\Model\TaxResultElement;

class CustomerAddressItemResolver extends AbstractAddressResolver
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

        $productTaxCode = $taxable->getContextValue(Taxable::PRODUCT_TAX_CODE);
        $accountTaxCode = $taxable->getContextValue(Taxable::ACCOUNT_TAX_CODE);

        $taxRules = $this->matcher->match($address, $productTaxCode, $accountTaxCode);
        $taxableUnitPrice = BigDecimal::of($taxable->getPrice());
        $taxableAmount = $taxableUnitPrice->multipliedBy($taxable->getQuantity());

        $result = $taxable->getResult();
        $this->resolveUnitPrice($result, $taxRules, $taxableUnitPrice);
        $this->resolveRowTotal($result, $taxRules, $taxableAmount);
    }

    /**
     * @param Result $result
     * @param TaxRule[] $taxRules
     * @param BigDecimal $taxableAmount
     */
    protected function resolveUnitPrice(Result $result, array $taxRules, BigDecimal $taxableAmount)
    {
        $taxRate = BigDecimal::zero();

        foreach ($taxRules as $taxRule) {
            $taxRate = $taxRate->plus($taxRule->getTax()->getRate());
        }

        $result->offsetSet(Result::UNIT, $this->calculator->calculate($taxableAmount, $taxRate));
    }

    /**
     * @param Result $result
     * @param TaxRule[] $taxRules
     * @param BigDecimal $taxableAmount
     */
    protected function resolveRowTotal(Result $result, array $taxRules, BigDecimal $taxableAmount)
    {
        $taxRate = BigDecimal::zero();

        $taxResults = [];

        if ($this->settingsProvider->isStartCalculationWithRowTotal()) {
            $taxableAmount = $taxableAmount->toScale(TaxCalculatorInterface::SCALE, RoundingMode::UP);
        }

        foreach ($taxRules as $taxRule) {
            $currentTaxRate = $taxRule->getTax()->getRate();
            $resultElement = $this->calculator->calculate($taxableAmount, $currentTaxRate);
            $taxRate = $taxRate->plus($currentTaxRate);

            $taxResults[] = TaxResultElement::create(
                (string)$taxRule->getTax(),
                $currentTaxRate,
                $resultElement->getExcludingTax(),
                $resultElement->getTaxAmount()
            );
        }

        $result->offsetSet(Result::ROW, $this->calculator->calculate($taxableAmount, $taxRate));
        $result->offsetSet(Result::TAXES, $taxResults);
    }
}
