<?php

namespace Oro\Bundle\TaxBundle\Event;

use Oro\Bundle\TaxBundle\Model\Taxable;
use Symfony\Contracts\EventDispatcher\Event;

class ResolveTaxEvent extends Event
{
    const RESOLVE_BEFORE = 'oro_tax.resolve_before';
    const RESOLVE = 'oro_tax.resolve';
    const RESOLVE_AFTER = 'oro_tax.resolve_after';

    /** @var Taxable */
    protected $taxable;

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
