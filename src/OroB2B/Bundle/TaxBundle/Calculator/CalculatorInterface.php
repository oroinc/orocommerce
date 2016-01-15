<?php

namespace OroB2B\Bundle\TaxBundle\Calculator;

use Doctrine\Common\Collections\Collection;

use OroB2B\Bundle\TaxBundle\Entity\TaxRule;
use OroB2B\Bundle\TaxBundle\Model\ResultElement;
use OroB2B\Bundle\TaxBundle\Model\Taxable;

interface CalculatorInterface
{
    const ADJUSTMENT_SCALE = 4;

    /**
     * @param Taxable $taxable
     * @param Collection|TaxRule[] $taxRules
     * @return ResultElement
     */
    public function calculate(Taxable $taxable, Collection $taxRules);
}
