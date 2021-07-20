<?php

namespace Oro\Bundle\RFPBundle\Layout\DataProvider;

use Oro\Bundle\PricingBundle\Layout\DataProvider\FrontendProductPricesProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\RFPBundle\Entity\Request as RFPRequest;
use Oro\Bundle\RFPBundle\Entity\RequestProduct;

class RfpProductPricesProvider
{
    /**
     * @var FrontendProductPricesProvider
     */
    private $productPricesProvider;

    public function __construct(FrontendProductPricesProvider $productPricesProvider)
    {
        $this->productPricesProvider = $productPricesProvider;
    }

    /**
     * @param RFPRequest $rfpRequest
     * @return array
     */
    public function getPrices(RFPRequest $rfpRequest)
    {
        /**
         * @var Product[] $products
         */
        $products = $rfpRequest->getRequestProducts()->map(function (RequestProduct $rfpProduct) {
            return $rfpProduct->getProduct();
        })->toArray();

        return $this->productPricesProvider->getByProducts($products);
    }
}
