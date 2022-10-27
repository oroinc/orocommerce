<?php

namespace Oro\Bundle\InventoryBundle\Validator;

use Oro\Bundle\CheckoutBundle\Entity\CheckoutLineItem;
use Oro\Bundle\InventoryBundle\Inventory\LowInventoryProvider;
use Symfony\Contracts\Translation\TranslatorInterface;

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
     */
    public function isLineItemRunningLow(CheckoutLineItem $lineItem)
    {
        $product = $lineItem->getProduct();
        $productUnit = $lineItem->getProductUnit();

        return $this->lowInventoryManager->isLowInventoryProduct($product, $productUnit);
    }

    /**
     * @param mixed $lineItem
     *
     * @return bool|string
     */
    public function getMessageIfLineItemRunningLow(CheckoutLineItem $lineItem)
    {
        $isValidCheckoutLineItem = $this->isLineItemRunningLow($lineItem);
        if ($isValidCheckoutLineItem) {
            return $this->translator->trans('oro.inventory.low_inventory.message');
        }

        return false;
    }
}
