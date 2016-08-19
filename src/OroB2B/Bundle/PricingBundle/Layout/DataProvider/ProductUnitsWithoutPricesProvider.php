<?php

namespace OroB2B\Bundle\PricingBundle\Layout\DataProvider;

use OroB2B\Bundle\PricingBundle\Model\PriceListRequestHandler;
use OroB2B\Bundle\ProductBundle\Entity\Product;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnit;
use OroB2B\Bundle\ProductBundle\Entity\ProductUnitPrecision;

class ProductUnitsWithoutPricesProvider
{
    /**
     * @var array
     */
    protected $productUnits = [];

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
     * @param Product $product
     *
     * @return ProductUnit[]
     */
    public function getProductUnits(Product $product)
    {
        if (!array_key_exists($product->getId(), $this->productUnits)) {
            $prices = $this->pricesProvider->getByProduct($product);

            $unitWithPrices = [];
            foreach ($prices as $price) {
                $unitWithPrices[] = $price->getUnit();
            }
            $units = $product->getUnitPrecisions()->map(
                function (ProductUnitPrecision $unitPrecision) {
                    return $unitPrecision->isSell() ? $unitPrecision->getUnit() : null;
                }
            )->toArray();

            foreach ($units as $key => $unit) {
                if (!$unit) {
                    unset($units[$key]);
                }
            }

            $this->productUnits[$product->getId()] = array_diff($units, $unitWithPrices);
        }

        return $this->productUnits[$product->getId()];
    }
}
