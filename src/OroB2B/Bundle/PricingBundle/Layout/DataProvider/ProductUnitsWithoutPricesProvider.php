<?php

namespace OroB2B\Bundle\PricingBundle\Layout\DataProvider;

use Oro\Component\Layout\ContextInterface;
use Oro\Component\Layout\AbstractServerRenderDataProvider;

use OroB2B\Bundle\PricingBundle\Model\PriceListRequestHandler;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;

class ProductUnitsWithoutPricesProvider extends AbstractServerRenderDataProvider
{
    /**
     * @var PriceListRequestHandler
     */
    protected $pricesProvider;

    /**
     * @param FrontendProductPricesProvider $pricesProvider
     */
    public function __construct(FrontendProductPricesProvider $pricesProvider)
    {
        $this->pricesProvider = $pricesProvider;
    }

    /**
     * {@inheritDoc}
     */
    public function getData(ContextInterface $context)
    {
        /** @var Product $product */
        $product = $context->data()->get('product');
        $prices = $this->pricesProvider->getData($context);
        $unitWithPricesCodes = [];
        foreach ($prices as $price) {
            $unitWithPricesCodes[$price->getUnit()->getCode()] = true;
        }
        $unitWithPricesCodes = array_keys($unitWithPricesCodes);
        $unitPrecisions = $product->getUnitPrecisions()->toArray();
        return array_filter($unitPrecisions, function (ProductUnitPrecision $unitPrecision) use ($unitWithPricesCodes) {
            return !in_array($unitPrecision->getUnit()->getCode(), $unitWithPricesCodes, true);
        });
    }
}
