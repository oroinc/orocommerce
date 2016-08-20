<?php

namespace Oro\Bundle\TaxBundle\Layout\Provider;

use Oro\Bundle\TaxBundle\Manager\TaxManager;
use Oro\Bundle\TaxBundle\Model\Result;

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
