<?php

namespace Oro\Bundle\InventoryBundle\Validator;

use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\InventoryBundle\Inventory\LowInventoryProvider;
use Oro\Bundle\ProductBundle\Model\ProductLineItemInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Checks if the line item product inventory level is running low.
 */
class LowInventoryCheckoutLineItemValidator
{
    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var LowInventoryProvider
     */
    protected $lowInventoryManager;

    public function __construct(
        LowInventoryProvider $lowInventoryProvider,
        TranslatorInterface $translator
    ) {
        $this->lowInventoryManager = $lowInventoryProvider;
        $this->translator = $translator;
    }

    /**
     * @param CheckoutLineItem $lineItem
     *
     * @return bool
     *
     * @deprecated since 5.1, use isRunningLow instead
     */
    public function isLineItemRunningLow(CheckoutLineItem $lineItem)
    {
        $product = $lineItem->getProduct();
        $productUnit = $lineItem->getProductUnit();

        return $this->lowInventoryManager->isLowInventoryProduct($product, $productUnit);
    }

    public function isRunningLow(ProductLineItemInterface $lineItem): bool
    {
        $product = $lineItem->getProduct();
        $productUnit = $lineItem->getProductUnit();

        return $this->lowInventoryManager->isLowInventoryProduct($product, $productUnit);
    }

    /**
     * @param mixed $lineItem
     *
     * @return bool|string
     *
     * @deprecated since 5.1, use getMessageIfRunningLow instead
     */
    public function getMessageIfLineItemRunningLow(CheckoutLineItem $lineItem)
    {
        $isValidCheckoutLineItem = $this->isLineItemRunningLow($lineItem);
        if ($isValidCheckoutLineItem) {
            return $this->translator->trans('oro.inventory.low_inventory.message');
        }

        return false;
    }

    public function getMessageIfRunningLow(ProductLineItemInterface $lineItem): ?string
    {
        $isValidCheckoutLineItem = $this->isRunningLow($lineItem);
        if ($isValidCheckoutLineItem) {
            return $this->translator->trans('oro.inventory.low_inventory.message');
        }

        return null;
    }
}
