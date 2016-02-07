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
        $resultElement->setAdjustment($adjustment);
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
            $currentTaxRate = $taxRule->getTax()->getRate();
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
     * @param $taxRate
     * @param int $quantity
     * @return array|ResultElement
     */
    protected function getRowTotalResult(BigDecimal $taxableAmount, $taxRate, $quantity = 1)
    {
        $resultElementStartWith = new ResultElement();
        if ($this->settingsProvider->isStartCalculationWithUnitPrice()) {
            $resultElement = $this->calculator->calculate($taxableAmount, $taxRate);
            $inclTax = BigDecimal::of($resultElement->getIncludingTax());
            $exclTax = BigDecimal::of($resultElement->getExcludingTax());
            $taxAmount = BigDecimal::of($resultElement->getTaxAmount());

            $inclTaxRounded = $inclTax->toScale(TaxationSettingsProvider::SCALE, RoundingMode::HALF_UP);
            $exclTaxRounded = $exclTax->toScale(TaxationSettingsProvider::SCALE, RoundingMode::HALF_UP);
            $taxAmountRounded = $taxAmount->toScale(TaxationSettingsProvider::SCALE, RoundingMode::HALF_UP);

            $adjustment = $taxAmount->minus($taxAmountRounded);
            $resultElementStartWith = ResultElement::create(
                $inclTaxRounded->multipliedBy($quantity),
                $exclTaxRounded->multipliedBy($quantity),
                $taxAmountRounded->multipliedBy($quantity),
                $adjustment->multipliedBy($quantity)
            );
            $resultElementStartWith = $this->adjustAmounts($resultElementStartWith);
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

    /**
     * @param ResultElement $data
     * @return ResultElement
     */
    protected function adjustAmounts(ResultElement $data)
    {
        $currentData = new ResultElement($data->getArrayCopy());

        if (!array_key_exists(ResultElement::ADJUSTMENT, $data)) {
            return $currentData;
        }

        $adjustment = BigDecimal::of($currentData[ResultElement::ADJUSTMENT]);
        $adjustmentAmounts = [ResultElement::TAX_AMOUNT => $adjustment];

        if ($this->settingsProvider->isProductPricesIncludeTax()) {
            $adjustmentAmounts[ResultElement::EXCLUDING_TAX] = $adjustment->negated();
        } else {
            $adjustmentAmounts[ResultElement::INCLUDING_TAX] = $adjustment;
        }

        foreach ($adjustmentAmounts as $key => $adjustment) {
            $amount = BigDecimal::of($currentData[$key])->plus($adjustment);
            $currentData[$key] = (string)$amount;
        }

        return $currentData;
    }
}
