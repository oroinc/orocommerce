<?php

namespace Oro\Bundle\InventoryBundle\Validator;

use Oro\Bundle\EntityBundle\Fallback\EntityFallbackResolver;
use Oro\Bundle\InventoryBundle\Migrations\Schema\v1_0\AddQuantityToOrderFields;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;

class QuantityToOrderValidatorService
{
    /**
     * @var EntityFallbackResolver
     */
    protected $fallbackResolver;

    /**
     * @param EntityFallbackResolver $fallbackResolver
     */
    public function __construct(EntityFallbackResolver $fallbackResolver)
    {
        $this->fallbackResolver = $fallbackResolver;
    }

    /**
     * @param LineItem[] $lineItems
     * @return bool
     */
    public function isLineItemListValid($lineItems)
    {
        foreach ($lineItems as $item) {
            if (!$item->getProduct() instanceof Product) {
                continue;
            }
            if ($this->isHigherThanMaxLimit($this->getMaximumLimit($item->getProduct()), $item->getQuantity())
                || $this->isLowerThenMinLimit($this->getMinimumLimit($item->getProduct()), $item->getQuantity())
            ) {
                return false;
            }
        }

        return true;
    }

    /**
     * @param mixed $maximumLimit
     * @param int $quantity
     * @return bool
     */
    public function isHigherThanMaxLimit($maximumLimit, $quantity)
    {
        if (!is_numeric($maximumLimit)) {
            return false;
        }

        return $quantity > $maximumLimit;
    }

    /**
     * @param mixed $minimumLimit
     * @param int $quantity
     * @return bool
     */
    public function isLowerThenMinLimit($minimumLimit, $quantity)
    {
        if (!is_numeric($minimumLimit)) {
            return false;
        }

        return $quantity < $minimumLimit;
    }

    /**
     * @param Product $product
     * @return bool
     */
    public function isMaxLimitLowerThenMinLimit(Product $product)
    {
        $minLimit = $this->getMinimumLimit($product);
        $maxLimit = $this->getMaximumLimit($product);
        if (!is_numeric($minLimit) || !is_numeric($maxLimit)) {
            return false;
        }

        return $maxLimit < $minLimit;
    }

    /**
     * @param Product $product
     * @return mixed
     */
    public function getMinimumLimit(Product $product)
    {
        return $this->fallbackResolver->getFallbackValue(
            $product,
            AddQuantityToOrderFields::FIELD_MINIMUM_QUANTITY_TO_ORDER
        );
    }

    /**
     * @param Product $product
     * @return mixed
     */
    public function getMaximumLimit(Product $product)
    {
        return $this->fallbackResolver->getFallbackValue(
            $product,
            AddQuantityToOrderFields::FIELD_MAXIMUM_QUANTITY_TO_ORDER
        );
    }
}
