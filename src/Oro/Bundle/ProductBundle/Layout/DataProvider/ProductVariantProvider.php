<?php

namespace Oro\Bundle\ProductBundle\Layout\DataProvider;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Provider\ProductVariantAvailabilityProvider;
use Oro\Component\Layout\DataAccessor;

/**
 * Provides information of currently available product variant.
 */
class ProductVariantProvider
{
    /** @var ProductVariantAvailabilityProvider */
    protected $availabilityProvider;

    public function __construct(ProductVariantAvailabilityProvider $availabilityProvider)
    {
        $this->availabilityProvider = $availabilityProvider;
    }

    /**
     * @param Product $configurableProduct
     * @return bool
     */
    public function hasProductAnyAvailableVariant(Product $configurableProduct)
    {
        return $this->availabilityProvider->hasSimpleProductsByVariantFields($configurableProduct);
    }

    /**
     * This method returns productVariant from the layout data or original product if variant doesn't exist
     *
     * @param DataAccessor $data
     * @return Product|null
     */
    public function getProductVariantOrProduct(DataAccessor $data)
    {
        if ($data->offsetExists('chosenProductVariant') && $data->offsetGet('chosenProductVariant')) {
            return $data['chosenProductVariant'];
        }

        if ($data->offsetExists('productVariant')) {
            return $data['productVariant'];
        }

        if ($data->offsetExists('product')) {
            return $data['product'];
        }

        throw new \InvalidArgumentException('Can not find product variant or product in layout update data');
    }
}
