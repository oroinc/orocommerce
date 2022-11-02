<?php

namespace Oro\Bundle\InventoryBundle\Validator;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\EntityBundle\Fallback\EntityFallbackResolver;
use Oro\Bundle\EntityBundle\Manager\PreloadingManager;
use Oro\Bundle\InventoryBundle\Model\Inventory;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Checks if shopping list line items follow minimum and maximum quantity restrictions.
 */
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
     * @var PreloadingManager
     */
    private $preloadingManager;

    public function __construct(
        EntityFallbackResolver $fallbackResolver,
        TranslatorInterface $translator,
        PreloadingManager $preloadingManager
    ) {
        $this->fallbackResolver = $fallbackResolver;
        $this->translator = $translator;
        $this->preloadingManager = $preloadingManager;
    }

    /**
     * @param Collection|ProductLineItemInterface[] $lineItems
     * @return bool
     */
    public function isLineItemListValid($lineItems)
    {
        $this->preloadingManager->preloadInEntities(
            $lineItems instanceof Collection ? $lineItems->toArray() : $lineItems,
            [
                'product' => [
                    'minimumQuantityToOrder' => [],
                    'maximumQuantityToOrder' => [],
                    'category' => [
                        'minimumQuantityToOrder' => [],
                        'maximumQuantityToOrder' => [],
                    ],
                ],
            ]
        );

        foreach ($lineItems as $item) {
            $product = $item->getProduct();
            $quantity = $item->getQuantity();
            if (!$product instanceof Product) {
                continue;
            }
            if ($this->isHigherThanMaxLimit($this->getMaximumLimit($product), $quantity)
                || $this->isLowerThenMinLimit($this->getMinimumLimit($product), $quantity)
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
            Inventory::FIELD_MINIMUM_QUANTITY_TO_ORDER
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
            Inventory::FIELD_MAXIMUM_QUANTITY_TO_ORDER
        );
    }

    /**
     * @param Product $product
     * @param int|float $quantity
     * @return bool|string
     */
    public function getMinimumErrorIfInvalid(Product $product, $quantity)
    {
        $minLimit = $this->getMinimumLimit($product);
        if ($this->isLowerThenMinLimit($minLimit, $quantity)) {
            return $this->translator->trans(
                'oro.inventory.product.error.quantity_below_min_limit',
                ['%limit%' => $minLimit]
            );
        }

        return false;
    }

    /**
     * @param Product $product
     * @param int|float $quantity
     * @return bool|string
     */
    public function getMaximumErrorIfInvalid(Product $product, $quantity)
    {
        $maxLimit = $this->getMaximumLimit($product);
        if (0 == $maxLimit) {
            return $this->translator->trans(
                'oro.inventory.product.error.quantity_limit_is_zero',
                [
                    '%sku%' => $product->getSku(),
                    '%product_name%' => $product->getDenormalizedDefaultName()
                ]
            );
        }

        if ($this->isHigherThanMaxLimit($maxLimit, $quantity)) {
            return $this->translator->trans(
                'oro.inventory.product.error.quantity_over_max_limit',
                ['%limit%' => $maxLimit]
            );
        }

        return false;
    }
}
