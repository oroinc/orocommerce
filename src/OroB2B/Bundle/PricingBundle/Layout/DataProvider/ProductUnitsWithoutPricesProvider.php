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
     * @var array
     */
    protected $data = [];

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
        if (!array_key_exists($product->getId(), $this->data)) {
            $prices = $this->pricesProvider->getData($context);

            $unitWithPrices = [];
            foreach ($prices as $price) {
                $unitWithPrices[] = $price->getUnit();
            }
            $units = $product->getUnitPrecisions()->map(
                function (ProductUnitPrecision $unitPrecision) {
                    return $unitPrecision->getUnit();
                }
            )->toArray();

            $this->data[$product->getId()] = array_diff($units, $unitWithPrices);
        }

        return $this->data[$product->getId()];
    }
}
