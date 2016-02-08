<?php

namespace OroB2B\Bundle\TaxBundle\Resolver;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;

use OroB2B\Bundle\TaxBundle\Entity\TaxRule;
use OroB2B\Bundle\TaxBundle\Model\Result;
use OroB2B\Bundle\TaxBundle\Model\ResultElement;
use OroB2B\Bundle\TaxBundle\Model\Taxable;
use OroB2B\Bundle\TaxBundle\Model\TaxResultElement;
use OroB2B\Bundle\TaxBundle\Provider\TaxationSettingsProvider;

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

        $taxRules = $this->matcher->match($address);
        $taxableAmount = BigDecimal::of($taxable->getPrice());

        $result = $taxable->getResult();
        $this->resolveUnitPrice($result, $taxRules, $taxableAmount);
        $this->resolveRowTotal($result, $taxRules, $taxableAmount, $taxable->getQuantity());
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

        $resultElement = $this->calculator->calculate($taxableAmount, $taxRate);
        $this->calculateAdjustment($resultElement);

        $result->offsetSet(Result::UNIT, $resultElement);
    }

    /**
     * @param ResultElement $resultElement
     */
    protected function calculateAdjustment(ResultElement $resultElement)
    {
        $taxAmount = BigDecimal::of($resultElement->getTaxAmount());
        $taxAmountRounded = $taxAmount->toScale(TaxationSettingsProvider::SCALE, RoundingMode::HALF_UP);
        $adjustment = $taxAmount->minus($taxAmountRounded);
        if (!$adjustment->isEqualTo(BigDecimal::zero())) {
            $resultElement->setAdjustment($adjustment);
        }
    }

    /**
     * @param Result $result
     * @param TaxRule[] $taxRules
     * @param BigDecimal $taxableAmount
     * @param int $quantity
     */
    protected function resolveRowTotal(Result $result, array $taxRules, BigDecimal $taxableAmount, $quantity)
    {
        $taxRate = BigDecimal::zero();

        $taxResults = [];

        foreach ($taxRules as $taxRule) {
            $currentTaxRate = BigDecimal::of($taxRule->getTax()->getRate());
            $taxRate = $taxRate->plus($currentTaxRate);

            $resultElement = $this->getRowTotalResult($taxableAmount, $currentTaxRate, $quantity);
            $taxResults[] = TaxResultElement::create(
                (string)$taxRule->getTax(),
                $currentTaxRate,
                $resultElement->getExcludingTax(),
                $resultElement->getTaxAmount()
            );
        }

        $resultElementStartWith = $this->getRowTotalResult($taxableAmount, $taxRate, $quantity);

        $result->offsetSet(Result::ROW, $resultElementStartWith);
        $result->offsetSet(Result::TAXES, $taxResults);
    }

    /**
     * @param BigDecimal $taxableAmount
     * @param BigDecimal $taxRate
     * @param int $quantity
     * @return ResultElement
     */
    protected function getRowTotalResult(BigDecimal $taxableAmount, BigDecimal $taxRate, $quantity = 1)
    {
        $resultElementStartWith = $this->calculator->calculate($taxableAmount, BigDecimal::zero());

        if ($this->settingsProvider->isStartCalculationWithUnitPrice()) {
            $resultElement = $this->calculator->calculate($taxableAmount, $taxRate);
            $amount = BigDecimal::of($resultElement->getOffset($this->calculator->getAmountKey()));
            $amountRounded = $amount->toScale(TaxationSettingsProvider::SCALE, RoundingMode::HALF_UP);

            $resultElement = $this->calculator->calculate($amount, $taxRate);
            $this->calculateAdjustment($resultElement);

            $resultElementStartWith = $this->calculator->calculate($amountRounded->multipliedBy($quantity), $taxRate);
            $resultElementStartWith->setAdjustment(
                BigDecimal::of($resultElement->getAdjustment())->multipliedBy($quantity)
            );
        } elseif ($this->settingsProvider->isStartCalculationWithRowTotal()) {
            $resultElementStartWith = $this->calculator->calculate(
                $taxableAmount->multipliedBy($quantity)->toScale(
                    TaxationSettingsProvider::SCALE,
                    RoundingMode::HALF_UP
                ),
                $taxRate
            );
            $this->calculateAdjustment($resultElementStartWith);
        }

        return $resultElementStartWith;
    }
}
