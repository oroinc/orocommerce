<?php

namespace Oro\Bundle\TaxBundle\Event;

use Oro\Bundle\TaxBundle\Model\Taxable;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * This event is used to decide if tax recalculation should be skipped.
 */
class SkipOrderTaxRecalculationEvent extends Event
{
    private Taxable $taxable;

    private bool $skipOrderTaxRecalculation = false;

    public function __construct(Taxable $taxable)
    {
        $this->taxable = $taxable;
    }

    public function getTaxable(): Taxable
    {
        return $this->taxable;
    }

    public function setSkipOrderTaxRecalculation(bool $skipOrderTaxRecalculation): self
    {
        $this->skipOrderTaxRecalculation = $skipOrderTaxRecalculation;

        if ($skipOrderTaxRecalculation === false) {
            $this->stopPropagation();
        }

        return $this;
    }

    public function isSkipOrderTaxRecalculation(): bool
    {
        return $this->skipOrderTaxRecalculation;
    }
}
