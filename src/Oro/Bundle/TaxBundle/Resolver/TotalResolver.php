<?php

namespace Oro\Bundle\TaxBundle\Resolver;

use Brick\Math\BigDecimal;
use Brick\Math\Exception\NumberFormatException;

use Oro\Bundle\TaxBundle\Model\Result;
use Oro\Bundle\TaxBundle\Model\ResultElement;
use Oro\Bundle\TaxBundle\Model\Taxable;
use Oro\Bundle\TaxBundle\Model\TaxResultElement;
use Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider;

class TotalResolver implements ResolverInterface
{
    /** @var TaxationSettingsProvider */
    protected $settingsProvider;

    /**  @var RoundingResolver */
    protected $roundingResolver;

    /**
     * @param TaxationSettingsProvider $settingsProvider
     * @param RoundingResolver $roundingResolver
     */
    public function __construct(TaxationSettingsProvider $settingsProvider, RoundingResolver $roundingResolver)
    {
        $this->settingsProvider = $settingsProvider;
        $this->roundingResolver = $roundingResolver;
    }

    /** {@inheritdoc} */
    public function resolve(Taxable $taxable)
    {
        if (!$taxable->getItems()->count()) {
            return;
        }

        if ($taxable->getResult()->isResultLocked()) {
            return;
        }

        $taxResults = [];
        $data = ResultElement::create(BigDecimal::zero(), BigDecimal::zero(), BigDecimal::zero(), BigDecimal::zero());

        foreach ($taxable->getItems() as $taxableItem) {
            $taxableItemResult = $taxableItem->getResult();
            $row = $taxableItemResult->getRow();

            if ($this->settingsProvider->isStartCalculationOnItem()) {
                $this->roundingResolver->round($row);
            }

            try {
                $mergedData = $this->mergeData($data, $row);
                $mergedTaxResults = $this->mergeTaxResultElements($taxResults, $taxableItemResult);
            } catch (NumberFormatException $e) {
                continue;
            }

            $data = $mergedData;
            $taxResults = $mergedTaxResults;
        }

        if ($this->settingsProvider->isStartCalculationOnTotal()) {
            try {
                $adjustedAmounts = $this->adjustAmounts($data);
            } catch (NumberFormatException $e) {
                return;
            }
            $data = $adjustedAmounts;
        }

        $result = $taxable->getResult();
        $result->offsetSet(Result::TOTAL, $data);
        $result->offsetSet(Result::TAXES, array_values($taxResults));
        $result->lockResult();
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
        $keysToAdjust = [ResultElement::TAX_AMOUNT => $adjustment];

        if ($this->settingsProvider->isProductPricesIncludeTax()) {
            $keysToAdjust[ResultElement::EXCLUDING_TAX] = $adjustment->negated();
        } else {
            $keysToAdjust[ResultElement::INCLUDING_TAX] = $adjustment;
        }

        foreach ($keysToAdjust as $key => $adjustment) {
            if ($currentData->offsetExists($key)) {
                $currentData->offsetSet($key, BigDecimal::of($currentData->getOffset($key))->plus($adjustment));
            }
        }

        return $currentData;
    }

    /**
     * @param TaxResultElement[] $taxResults
     * @param Result $taxableItemResult
     * @return TaxResultElement[]
     */
    protected function mergeTaxResultElements(array $taxResults, Result $taxableItemResult)
    {
        foreach ($taxableItemResult->getTaxes() as $appliedTax) {
            $taxCode = (string)$appliedTax->getTax();
            $taxAmount = $appliedTax->getTaxAmount();
            $taxableAmount = $appliedTax->getTaxableAmount();
            if (array_key_exists($taxCode, $taxResults)) {
                $tax = $taxResults[$taxCode];
                $taxAmount = BigDecimal::of($tax->getTaxAmount())->plus($taxAmount);
                $taxableAmount = BigDecimal::of($tax->getTaxableAmount())->plus($taxableAmount);
            }

            $taxResults[$taxCode] = TaxResultElement::create(
                $taxCode,
                $appliedTax->getRate(),
                $taxableAmount,
                $taxAmount
            );
        }

        return $taxResults;
    }

    /**
     * @param ResultElement $target
     * @param ResultElement $source
     * @return ResultElement
     */
    protected function mergeData(ResultElement $target, ResultElement $source)
    {
        $currentData = new ResultElement($target->getArrayCopy());

        foreach ($source as $key => $value) {
            $currentValue = BigDecimal::of($currentData->offsetGet($key));
            $currentValue = $currentValue->plus($value);
            $currentData->offsetSet($key, (string)$currentValue);
        }

        return $currentData;
    }
}
