<?php

namespace Oro\Bundle\RFPBundle\Layout\DataProvider;

use Oro\Bundle\PricingBundle\Provider\ProductPriceProvider;
use Oro\Component\Layout\DataProvider\AbstractFormProvider;

class TierPricesProvider extends AbstractFormProvider
{
    /**
     * @var ProductPriceProvider
     */
    private $productPriceProvider;

    /**
     * @param ProductPriceProvider $productPriceProvider
     */
    public function __construct(ProductPriceProvider $productPriceProvider)
    {
        $this->productPriceProvider = $productPriceProvider;
    }

    public function getPrices($vars)
    {
        var_dump($vars);
        exit;
    }
}
