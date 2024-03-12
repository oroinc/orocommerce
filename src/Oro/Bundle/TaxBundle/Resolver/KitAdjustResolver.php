<?php

namespace Oro\Bundle\TaxBundle\Resolver;

use Brick\Math\BigDecimal;
use Oro\Bundle\TaxBundle\Model\Result;
use Oro\Bundle\TaxBundle\Model\ResultElement;
use Oro\Bundle\TaxBundle\Model\Taxable;
use Oro\Bundle\TaxBundle\Model\TaxResultElement;
use Oro\Bundle\TaxBundle\Provider\TaxationSettingsProvider;

/**
 * Adjust row, unit and taxes for kit Taxable.
 */
class KitAdjustResolver implements ResolverInterface
{
    use TaxCalculateResolverTrait;

    public function __construct(
        private TaxationSettingsProvider $settingsProvider,
        private RoundingResolver $roundingResolver
    ) {
    }

    public function resolve(Taxable $taxable): void
    {
        if (!$this->isApplicable($taxable)) {
            return;
        }

        if ($taxable->isKitTaxable()) {
            $this->adjustKitTaxable($taxable);
            return;
        }

        foreach ($taxable->getItems() as $taxableItem) {
            if (!$taxableItem->isKitTaxable()) {
                continue;
            }

            $this->adjustKitTaxable($taxableItem);
        }
    }

    private function isApplicable(Taxable $taxable): bool
    {
        return $taxable->getItems()->count();
    }

    private function adjustKitTaxable($taxable): void
    {
        $this->adjustKitUnit($taxable);
        $this->adjustKitRow($taxable);
        $this->adjustKitTaxes($taxable);
    }

    private function adjustKitUnit(Taxable $taxable): void
    {
        $kitUnit = $taxable->getResult()->getUnit();
        $kitIncludingTax = BigDecimal::of($kitUnit->getIncludingTax() ?? 0);
        $kitExcludingTax = BigDecimal::of($kitUnit->getExcludingTax() ?? 0);
        $kitTaxAmount = BigDecimal::of($kitUnit->getTaxAmount() ?? 0);

        foreach ($taxable->getItems() as $item) {
            $quantity = $item->getQuantity();
            $unit = $item->getResult()->getUnit();
            extract($unit->jsonSerialize()); # $includingTax, $excludingTax, $taxAmount
            if (count($unit) <= 0) {
                $includingTax = $excludingTax = $item->getPrice();
                $taxAmount = 0;
            }

            // Multiplied kit item price by kit item quantity
            $kitIncludingTax = $kitIncludingTax->plus(BigDecimal::of($includingTax ?? 0)->multipliedBy($quantity));
            $kitExcludingTax = $kitExcludingTax->plus(BigDecimal::of($excludingTax ?? 0)->multipliedBy($quantity));
            $kitTaxAmount = $kitTaxAmount->plus(BigDecimal::of($taxAmount ?? 0)->multipliedBy($quantity));
        }

        $kitUnit = ResultElement::create($kitIncludingTax, $kitExcludingTax, $kitTaxAmount);

        $this->calculateAdjustment($kitUnit);

        $taxable->getResult()->offsetSet(Result::UNIT, $kitUnit);
    }

    private function adjustKitRow(Taxable $taxable): void
    {
        $kitRow = $taxable->getResult()->getRow();
        $isDiscountsIncluded = $kitRow->isDiscountsIncluded();
        foreach ($taxable->getItems() as $item) {
            $row = $item->getResult()->getRow();
            extract($row->jsonSerialize()); # $includingTax, $excludingTax, $taxAmount
            if (count($row) <= 0) {
                $includingTax = $excludingTax = $item->getPrice();
                $taxAmount = 0;
            }

            $row = ResultElement::create(
                BigDecimal::of($includingTax ?? 0)->multipliedBy($taxable->getQuantity()),
                BigDecimal::of($excludingTax ?? 0)->multipliedBy($taxable->getQuantity()),
                BigDecimal::of($taxAmount ?? 0)->multipliedBy($taxable->getQuantity())
            );

            $kitRow = $this->mergeData($row, $kitRow);
        }

        if ($isDiscountsIncluded) {
            $kitRow->setDiscountsIncluded(true);
        }

        $this->calculateAdjustment($kitRow);

        $taxable->getResult()->offsetSet(Result::ROW, $kitRow);
    }

    private function adjustKitTaxes(Taxable $taxable): void
    {
        $kitTaxes = [];
        foreach ($taxable->getResult()->getTaxes() as $kitTax) {
            $kitTaxes[$kitTax->getTax()] = $kitTax;
        }

        foreach ($taxable->getItems() as $item) {
            foreach ($item->getResult()->getTaxes() as $tax) {
                // Multiplied kit item price by kit quantity
                $taxAmount = BigDecimal::of($tax->getTaxAmount())->multipliedBy($taxable->getQuantity());
                $taxableAmount = BigDecimal::of($tax->getTaxableAmount())->multipliedBy($taxable->getQuantity());

                if (array_key_exists($tax->getTax(), $kitTaxes)) {
                    $tax = $kitTaxes[$tax->getTax()];
                    $taxAmount = BigDecimal::of($tax->getTaxAmount())->plus($taxAmount);
                    $taxableAmount = BigDecimal::of($tax->getTaxableAmount())->plus($taxableAmount);
                }

                $taxResult = TaxResultElement::create(
                    $tax->getTax(),
                    BigDecimal::of($tax->getRate()),
                    $taxableAmount,
                    $taxAmount
                );

                if ($this->settingsProvider->isStartCalculationOnItem()) {
                    $this->roundingResolver->round($taxResult);
                }

                $kitTaxes[$tax->getTax()] = $taxResult;
            }
        }

        $taxable->getResult()->offsetSet(Result::TAXES, array_values($kitTaxes));
    }
}
