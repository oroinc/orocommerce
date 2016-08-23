<?php

namespace Oro\Bundle\TaxBundle\Event;

use Symfony\Component\EventDispatcher\Event;

use Oro\Bundle\TaxBundle\Model\Taxable;

class ResolveTaxEvent extends Event
{
    const RESOLVE_BEFORE = 'orob2b_tax.resolve_before';
    const RESOLVE = 'orob2b_tax.resolve';
    const RESOLVE_AFTER = 'orob2b_tax.resolve_after';

    /** @var Taxable */
    protected $taxable;

    /**
     * @param Taxable $taxable
     */
    public function __construct(Taxable $taxable)
    {
        $this->taxable = $taxable;
    }

    /**
     * @return Taxable
     */
    public function getTaxable()
    {
        return $this->taxable;
    }
}
