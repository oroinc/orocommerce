<?php

namespace OroB2B\Bundle\TaxBundle\Calculator;

use Doctrine\Common\Collections\Collection;

use OroB2B\Bundle\ProductBundle\Rounding\RoundingServiceInterface;
use OroB2B\Bundle\TaxBundle\Entity\TaxRule;

abstract class AbstractCalculator
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
     * @param Collection|TaxRule[] $taxRules
     * @return float
     */
    protected function getTaxRate(Collection $taxRules)
    {
        $taxRate = 0.00;

        $taxRules->map(
            function (TaxRule $taxRule) use (&$taxRate) {
                $taxRate += $taxRule->getTax()->getRate();
            }
        );

        return abs($taxRate);
    }
}
