<?php

namespace Oro\Bundle\RFPBundle\Layout\DataProvider;

use Oro\Bundle\PricingBundle\Layout\DataProvider\FrontendProductPricesProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\RFPBundle\Entity\Request as RFPRequest;
use Oro\Bundle\RFPBundle\Entity\RequestProduct;

/**
 * Calculates prices of a RFP request products.
 */
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
        /** @var Product[] $products */
        $products = [];
        /** @var RequestProduct $rfpProduct */
        foreach ($rfpRequest->getRequestProducts() as $rfpProduct) {
            if ($rfpProduct->getProduct()) {
                $products[] = $rfpProduct->getProduct();
            }
        }

        return $this->productPricesProvider->getByProducts($products);
    }
}
