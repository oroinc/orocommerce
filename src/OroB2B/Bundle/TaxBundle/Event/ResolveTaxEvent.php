<?php

namespace OroB2B\Bundle\TaxBundle\Event;

use Symfony\Component\EventDispatcher\Event;

use OroB2B\Bundle\TaxBundle\Model\Result;
use OroB2B\Bundle\TaxBundle\Model\Taxable;

class ResolveTaxEvent extends Event
{
    const NAME = 'orob2b_tax.resolve';

    /** @var Taxable */
    protected $taxable;

    /** @var Result */
    protected $result;

    /**
     * @param Taxable $taxable
     * @param Result $result
     */
    public function __construct(Taxable $taxable, Result $result)
    {
        $this->taxable = $taxable;
        $this->result = $result;
    }

    /**
     * @return Taxable
     */
    public function getTaxable()
    {
        return $this->taxable;
    }

    /**
     * @return Result
     */
    public function getResult()
    {
        return $this->result;
    }
}
