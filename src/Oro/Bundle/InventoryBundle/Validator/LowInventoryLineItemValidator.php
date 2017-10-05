<?php

namespace Oro\Bundle\InventoryBundle\Validator;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\InventoryBundle\Inventory\LowInventoryQuantityManager;

class LowInventoryLineItemValidator
{
    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var LowInventoryQuantityManager
     */
    protected $lowInventoryManager;

    /**
     * @param LowInventoryQuantityManager $lowInventoryQuantityManager
     * @param TranslatorInterface $translator
     */
    public function __construct(
        LowInventoryQuantityManager $lowInventoryQuantityManager,
        TranslatorInterface $translator
    ) {
        $this->lowInventoryManager = $lowInventoryQuantityManager;
        $this->translator = $translator;
    }

    /**
     * @param LineItem $lineItem
     *
     * @return bool|string
     */
    public function getLowInventoryMessage(LineItem $lineItem)
    {
        $product = $lineItem->getProduct();
        $productUnit = $lineItem->getProductUnit();

        $isLowInventory = $this->lowInventoryManager->isLowInventoryProduct($product, $productUnit);
        if ($isLowInventory) {
            return $this->translator->trans('oro.inventory.low_inventory.message');
        }

        return false;
    }
}
