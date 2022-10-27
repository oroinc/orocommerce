<?php

namespace Oro\Bundle\InventoryBundle\EventListener;

use Oro\Bundle\CheckoutBundle\DataProvider\Manager\CheckoutLineItemsManager;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\InventoryBundle\Entity\Repository\InventoryLevelRepository;
use Oro\Bundle\InventoryBundle\Exception\InventoryLevelNotFoundException;
use Oro\Bundle\InventoryBundle\Inventory\InventoryQuantityManager;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ShoppingListBundle\Event\LineItemValidateEvent;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Adds validation errors to LineItemValidateEvent.
 */
class CreateOrderLineItemValidationListener
{
    /**
     * @var InventoryQuantityManager
     */
    protected $inventoryQuantityManager;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var CheckoutLineItemsManager
     */
    protected $checkoutLineItemsManager;

    /**
     * @var array
     */
    protected static $allowedValidationSteps = ['order_review', 'checkout', 'request_approval', 'approve_request'];

    public function __construct(
        InventoryQuantityManager $inventoryQuantityManager,
        DoctrineHelper $doctrineHelper,
        TranslatorInterface $translator,
        CheckoutLineItemsManager $checkoutLineItemsManager
    ) {
        $this->inventoryQuantityManager = $inventoryQuantityManager;
        $this->translator = $translator;
        $this->doctrineHelper = $doctrineHelper;
        $this->checkoutLineItemsManager = $checkoutLineItemsManager;
    }

    /**
     * @throws InventoryLevelNotFoundException
     */
    public function onLineItemValidate(LineItemValidateEvent $event)
    {
        if (!$this->isContextSupported($event->getContext())) {
            return;
        }

        $lineItems = $this->checkoutLineItemsManager->getData($event->getContext()->getEntity());
        /** @var OrderLineItem $lineItem */
        foreach ($lineItems as $lineItem) {
            if (!$this->inventoryQuantityManager->shouldDecrement($lineItem->getProduct())) {
                continue;
            }

            $inventoryLevel = $this->getInventoryLevel($lineItem->getProduct(), $lineItem->getProductUnit());
            if (!$inventoryLevel) {
                throw new InventoryLevelNotFoundException();
            }

            if (!$this->inventoryQuantityManager->hasEnoughQuantity($inventoryLevel, $lineItem->getQuantity())) {
                $event->addErrorByUnit(
                    $lineItem->getProductSku(),
                    $lineItem->getProductUnitCode(),
                    $this->translator->trans('oro.inventory.decrement_inventory.product.not_allowed')
                );
            }
        }
    }

    /**
     * @param mixed $context
     * @return bool
     */
    protected function isContextSupported($context)
    {
        return ($context instanceof WorkflowItem
            && in_array($context->getCurrentStep()->getName(), self::$allowedValidationSteps)
            && $context->getEntity() instanceof Checkout
        );
    }

    /**
     * @param Product $product
     * @param ProductUnit $productUnit
     * @return InventoryLevel
     */
    protected function getInventoryLevel(Product $product, ProductUnit $productUnit)
    {
        /** @var InventoryLevelRepository $repository */
        $repository = $this->doctrineHelper->getEntityRepository(InventoryLevel::class);
        return $repository->getLevelByProductAndProductUnit(
            $product,
            $productUnit
        );
    }
}
