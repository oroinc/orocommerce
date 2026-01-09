<?php

namespace Oro\Bundle\TaxBundle\Event;

use Oro\Bundle\TaxBundle\Model\Taxable;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Event dispatched during tax calculation to allow resolvers to compute taxes.
 *
 * This event is dispatched in three phases (RESOLVE_BEFORE, RESOLVE, and RESOLVE_AFTER)
 * to allow different tax resolvers to participate in the tax calculation process.
 * The event carries a {@see Taxable} object that contains all the information needed
 * for tax calculation and accumulates the calculated tax results.
 */
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
