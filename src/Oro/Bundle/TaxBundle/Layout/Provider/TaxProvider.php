<?php

namespace Oro\Bundle\TaxBundle\Layout\Provider;

use Oro\Bundle\TaxBundle\Model\Result;
use Oro\Bundle\TaxBundle\Provider\TaxProviderRegistry;

class TaxProvider
{
    /** @var TaxProviderRegistry */
    private $taxProviderRegistry;

    public function __construct(TaxProviderRegistry $taxProviderRegistry)
    {
        $this->taxProviderRegistry = $taxProviderRegistry;
    }

    /**
     * @param $order
     *
     * @return Result
     */
    public function getTax($order)
    {
        $provider = $this->taxProviderRegistry->getEnabledProvider();

        return $provider->loadTax($order);
    }
}
