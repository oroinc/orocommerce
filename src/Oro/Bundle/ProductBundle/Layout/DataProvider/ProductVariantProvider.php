<?php

namespace Oro\Bundle\ProductBundle\Layout\DataProvider;

use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Provider\ProductVariantAvailabilityProvider;
use Oro\Component\Layout\DataAccessor;

class ProductVariantProvider
{
    /** @var ProductVariantAvailabilityProvider */
    protected $availabilityProvider;

    /**
     * @param ProductVariantAvailabilityProvider $availabilityProvider
     */
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
        $productVariants = $this->availabilityProvider->getSimpleProductsByVariantFields($configurableProduct);

        return (bool)count($productVariants);
    }

    /**
     * This method returns productVariant from the layout data or original product if variant doesn't exist
     *
     * @param DataAccessor $data
     * @return Product|null
     */
    public function getProductVariantOrProduct(DataAccessor $data)
    {
        if ($data->offsetExists('productVariant')) {
            return $data['productVariant'];
        } elseif ($data->offsetExists('product')) {
            return $data['product'];
        }

        throw new \InvalidArgumentException('Can not find product variant or product in layout update data');
    }
}
