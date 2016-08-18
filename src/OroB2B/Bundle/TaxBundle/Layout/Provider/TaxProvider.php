<?php

namespace OroB2B\Bundle\TaxBundle\Layout\Provider;

use OroB2B\Bundle\TaxBundle\Manager\TaxManager;
use OroB2B\Bundle\TaxBundle\Model\Result;

class TaxProvider
{
    /** @var TaxManager */
    protected $taxManager;

    /**
     * @param TaxManager $taxManager
     */
    public function __construct(TaxManager $taxManager)
    {
        $this->taxManager = $taxManager;
    }

    /**
     * @param $order
     *
     * @return Result
     */
    public function getTax($order)
    {
        return $this->taxManager->loadTax($order);
    }
}
