<?php

namespace Oro\Bundle\PromotionBundle\Discount;

use Oro\Bundle\ProductBundle\Entity\Product;

/**
 * Discounts will modify? prices, add discount information, may change structure of line items collection
 *
 * Important!!! Discount services MUST BE registered with shared: false
 */
interface DiscountInterface
{
    // TODO: Discount should contain information about promotion (source) for example it's labels (name?) are required
    // TODO -> for rendering data on frontend

    /**
     * @param array $options
     */
    public function configure(array $options);

    /**
     * @param \Traversable|Product[] $products
     */
    public function setMatchingProducts(\Traversable $products);

    /**
     * @return string
     */
    public function __toString(): string;

    /**
     * @param DiscountContext $discountContext
     */
    public function apply(DiscountContext $discountContext);
}
