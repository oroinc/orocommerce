<?php

namespace OroB2B\Bundle\PricingBundle\Layout\DataProvider;

use Oro\Component\Layout\AbstractServerRenderDataProvider;
use Oro\Component\Layout\ContextInterface;

use OroB2B\Bundle\ProductBundle\Layout\DataProvider\FeaturedProductsProvider;

class FeaturedProductsPricesProvider extends AbstractServerRenderDataProvider
{
    /**
     * @var FrontendProductPricesProvider
     */
    protected $productPricesProvider;

    /**
     * @var FeaturedProductsProvider
     */
    protected $featuredProductsProvider;

    /**
     * @param FrontendProductPricesProvider $productPricesProvider
     * @param FeaturedProductsProvider $productPricesProvider
     */
    public function __construct(
        FrontendProductPricesProvider $productPricesProvider,
        FeaturedProductsProvider $featuredProductsProvider
    ) {
        $this->productPricesProvider = $productPricesProvider;
        $this->featuredProductsProvider = $featuredProductsProvider;
    }

    /**
     * @inheritdoc
     */
    public function getData(ContextInterface $context)
    {
        $products = $this->featuredProductsProvider->getData($context);
        return $this->productPricesProvider->getProductsPrices($products);
    }
}
