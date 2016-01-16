<?php

namespace OroB2B\Bundle\TaxBundle\Calculator;

use OroB2B\Bundle\ProductBundle\Rounding\RoundingServiceInterface;
use OroB2B\Bundle\TaxBundle\Model\ResultElement;
use OroB2B\Bundle\TaxBundle\Rounding\TaxRoundingService;

abstract class AbstractRoundingTaxCalculator implements TaxCalculatorInterface
{
    /** @var RoundingServiceInterface */
    protected $roundingService;

    /**
     * @param RoundingServiceInterface $roundingService
     */
    public function __construct(RoundingServiceInterface $roundingService)
    {
        $this->roundingService = $roundingService;
    }

    /**
     * @param float $includingTax
     * @param float $excludingTax
     * @param float $taxAmount
     * @param float $adjustment
     *
     * @return ResultElement
     */
    protected function returnRoundedResult($includingTax, $excludingTax, $taxAmount, $adjustment)
    {
        return ResultElement::create(
            $this->roundingService->round($includingTax),
            $this->roundingService->round(
                $excludingTax,
                TaxRoundingService::TAX_PRECISION,
                TaxRoundingService::HALF_DOWN
            ),
            $this->roundingService->round($taxAmount),
            $this->roundingService->round($adjustment, self::ADJUSTMENT_SCALE)
        );
    }
}
