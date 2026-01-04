<?php

namespace Oro\Bundle\TaxBundle\Event;

use Oro\Bundle\TaxBundle\Model\Taxable;
use Symfony\Contracts\EventDispatcher\Event;

class ResolveTaxEvent extends Event
{
    public const RESOLVE_BEFORE = 'oro_tax.resolve_before';
    public const RESOLVE = 'oro_tax.resolve';
    public const RESOLVE_AFTER = 'oro_tax.resolve_after';

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
