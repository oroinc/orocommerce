<?php

namespace Oro\Bundle\InventoryBundle\Validator;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\EntityBundle\Fallback\EntityFallbackResolver;
use Oro\Bundle\InventoryBundle\Migrations\Schema\v1_1\AddQuantityToOrderFields;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;

class QuantityToOrderValidatorService
{
    /**
     * @var EntityFallbackResolver
     */
    protected $fallbackResolver;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param EntityFallbackResolver $fallbackResolver
     * @param TranslatorInterface $translator
     */
    public function __construct(EntityFallbackResolver $fallbackResolver, TranslatorInterface $translator)
    {
        $this->fallbackResolver = $fallbackResolver;
        $this->translator = $translator;
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

    /**
     * @param Product $product
     * @param $quantity
     * @return bool|string
     */
    public function getMinimumErrorIfInvalid(Product $product, $quantity)
    {
        $minLimit = $this->getMinimumLimit($product);
        if ($this->isLowerThenMinLimit($minLimit, $quantity)) {
            return $this->getErrorMessage($product, $minLimit, 'quantity_below_min_limit');
        }

        return false;
    }

    /**
     * @param Product $product
     * @param $quantity
     * @return bool|string
     */
    public function getMaximumErrorIfInvalid(Product $product, $quantity)
    {
        $maxLimit = $this->getMaximumLimit($product);
        if (0 == $maxLimit) {
            return $this->getErrorMessage($product, $maxLimit, 'quantity_limit_is_zero');
        }

        if ($this->isHigherThanMaxLimit($maxLimit, $quantity)) {
            return $this->getErrorMessage($product, $maxLimit, 'quantity_over_max_limit');
        }

        return false;
    }

    /**
     * @param Product $product
     * @param int $limit
     * @param string $messageSuffix
     * @return string
     */
    protected function getErrorMessage(Product $product, $limit, $messageSuffix)
    {
        return $this->translator->trans(
            'oro.inventory.product.error.' . $messageSuffix,
            [
                '%limit%' => $limit,
                '%sku%' => $product->getSku(),
                '%product_name%' => $product->getName(),
            ]
        );
    }
}
