<?php

namespace Oro\Bundle\InventoryBundle\EventListener;

use Doctrine\Persistence\ManagerRegistry;
use Oro\Bundle\CheckoutBundle\DataProvider\Manager\CheckoutLineItemsManager;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\InventoryBundle\Inventory\InventoryQuantityManager;
use Oro\Bundle\ShoppingListBundle\Event\LineItemValidateEvent;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Validates that all products have enough quantity in the stock.
 */
class CreateOrderLineItemValidationListener
{
    private const ALLOWED_STEPS = [
        'order_review' => true,
        'checkout' => true,
        // additional steps for alternative checkout
        'request_approval' => true,
        'approve_request' => true,
    ];

    private InventoryQuantityManager $quantityManager;
    private ManagerRegistry $doctrine;
    private TranslatorInterface $translator;
    private CheckoutLineItemsManager $checkoutLineItemsManager;

    public function __construct(
        InventoryQuantityManager $quantityManager,
        ManagerRegistry $doctrine,
        TranslatorInterface $translator,
        CheckoutLineItemsManager $checkoutLineItemsManager
    ) {
        $this->quantityManager = $quantityManager;
        $this->translator = $translator;
        $this->doctrine = $doctrine;
        $this->checkoutLineItemsManager = $checkoutLineItemsManager;
    }

    public function onLineItemValidate(LineItemValidateEvent $event): void
    {
        $context = $event->getContext();
        if (!$this->isContextSupported($context)) {
            return;
        }

        $lineItems = $this->checkoutLineItemsManager->getData($context->getEntity());
        foreach ($lineItems as $lineItem) {
            if (!$this->quantityManager->shouldDecrement($lineItem->getProduct())) {
                continue;
            }

            $inventoryLevel = $this->doctrine->getRepository(InventoryLevel::class)
                ->getLevelByProductAndProductUnit($lineItem->getProduct(), $lineItem->getProductUnit());
            if (null === $inventoryLevel
                || !$this->quantityManager->hasEnoughQuantity($inventoryLevel, $lineItem->getQuantity())
            ) {
                $event->addErrorByUnit(
                    $lineItem->getProductSku(),
                    $lineItem->getProductUnitCode(),
                    $this->translator->trans('oro.inventory.decrement_inventory.product.not_allowed')
                );
            }
        }
    }

    private function isContextSupported(mixed $context): bool
    {
        return
            $context instanceof WorkflowItem
            && $context->getEntity() instanceof Checkout
            && isset(self::ALLOWED_STEPS[$context->getCurrentStep()->getName()]);
    }
}
