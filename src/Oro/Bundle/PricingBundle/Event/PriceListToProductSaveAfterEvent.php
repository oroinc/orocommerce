<?php

namespace Oro\Bundle\PricingBundle\Event;

use Oro\Bundle\PricingBundle\Entity\PriceListToProduct;
use Symfony\Contracts\EventDispatcher\Event;

/**
 * Dispatched after a price list to product relationship is saved.
 *
 * This event carries the saved {@see PriceListToProduct} entity, allowing listeners to react
 * to changes in product assignments to price lists.
 */
class PriceListToProductSaveAfterEvent extends Event
{
    const NAME = 'oro_pricing.price_list_to_product.save_after';

    /**
     * @var PriceListToProduct
     */
    protected $priceListToProduct;

    public function __construct(PriceListToProduct $priceListToProduct)
    {
        $this->priceListToProduct = $priceListToProduct;
    }

    /**
     * @return PriceListToProduct
     */
    public function getPriceListToProduct()
    {
        return $this->priceListToProduct;
    }
}
