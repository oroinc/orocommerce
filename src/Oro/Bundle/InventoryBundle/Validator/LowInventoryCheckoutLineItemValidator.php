<?php

namespace Oro\Bundle\InventoryBundle\Validator;

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

    public function isRunningLow(ProductLineItemInterface $lineItem): bool
    {
        $product = $lineItem->getProduct();
        $productUnit = $lineItem->getProductUnit();

        return $this->lowInventoryManager->isLowInventoryProduct($product, $productUnit);
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
