<?php

namespace Oro\Bundle\TaxBundle\Resolver;

use Brick\Math\BigDecimal;
use Brick\Math\Exception\NumberFormatException;
use Oro\Bundle\TaxBundle\Model\Result;
use Oro\Bundle\TaxBundle\Model\ResultElement;
use Oro\Bundle\TaxBundle\Model\Taxable;
use Oro\Bundle\TaxBundle\Model\TaxResultElement;
use Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider;

/**
 * Tax resolver that combines all previous calculated tax values and provides total result.
 */
class TotalResolver implements ResolverInterface
{
    use TaxCalculateResolverTrait;

    public function __construct(
        private TaxationSettingsProvider $settingsProvider,
        private RoundingResolver $roundingResolver
    ) {
    }

    #[\Override]
    public function resolve(Taxable $taxable): void
    {
        if (!$this->isApplicable($taxable)) {
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

        $data = $this->mergeShippingData($taxable, $data);

        $result = $taxable->getResult();
        $result->offsetSet(Result::TOTAL, $data);
        $result->offsetSet(Result::TAXES, array_values($taxResults));
        $result->lockResult();
    }

    /**
     * @param TaxResultElement[] $taxResults
     * @return TaxResultElement[]
     */
    protected function mergeTaxResultElements(array $taxResults, Result $taxableItemResult): array
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

    protected function mergeShippingData(Taxable $taxable, ResultElement $target): ResultElement
    {
        if (!$taxable->getResult()->offsetExists(Result::SHIPPING)) {
            return $target;
        }

        $resultElement = $taxable->getResult()->offsetGet(Result::SHIPPING);

        return $this->mergeData($target, $resultElement);
    }

    private function isApplicable(Taxable $taxable): bool
    {
        return $taxable->getItems()->count() &&
            !$taxable->isKitTaxable() &&
            !$taxable->getResult()->isResultLocked();
    }
}
