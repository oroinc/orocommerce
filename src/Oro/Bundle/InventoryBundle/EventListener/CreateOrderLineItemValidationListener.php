<?php

namespace Oro\Bundle\InventoryBundle\EventListener;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Component\Checkout\LineItem\CheckoutLineItemInterface;
use Oro\Component\Checkout\LineItem\CheckoutLineItemsHolderInterface;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutSource;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\InventoryBundle\Entity\Repository\InventoryLevelRepository;
use Oro\Bundle\InventoryBundle\Exception\InventoryLevelNotFoundException;
use Oro\Bundle\InventoryBundle\Inventory\InventoryQuantityManager;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ShoppingListBundle\Event\LineItemValidateEvent;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;

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
     * @var RequestStack
     */
    protected $requestStack;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /**
     * @var array
     */
    protected static $allowedValidationSteps = ['order_review', 'checkout'];

    /**
     * @param InventoryQuantityManager $inventoryQuantityManager
     * @param DoctrineHelper $doctrineHelper
     * @param TranslatorInterface $translator
     */
    public function __construct(
        InventoryQuantityManager $inventoryQuantityManager,
        DoctrineHelper $doctrineHelper,
        TranslatorInterface $translator
    ) {
        $this->inventoryQuantityManager = $inventoryQuantityManager;
        $this->translator = $translator;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @param LineItemValidateEvent $event
     * @throws InventoryLevelNotFoundException
     */
    public function onLineItemValidate(LineItemValidateEvent $event)
    {
        if (!$this->isContextSupported($event->getContext())) {
            return;
        }

        $lineItems = $event->getContext()->getEntity()->getSource()->getEntity()->getLineItems();
        /** @var CheckoutLineItemInterface $lineItem */
        foreach ($lineItems as $lineItem) {
            if (!$this->inventoryQuantityManager->shouldDecrement($lineItem->getProduct())) {
                continue;
            }

            $inventoryLevel = $this->getInventoryLevel($lineItem->getProduct(), $lineItem->getProductUnit());
            if (!$inventoryLevel) {
                throw new InventoryLevelNotFoundException();
            }

            if (!$this->inventoryQuantityManager->hasEnoughQuantity($inventoryLevel, $lineItem->getQuantity())) {
                $event->addError(
                    $lineItem->getProductSku(),
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
            && $context->getEntity()->getSource() instanceof CheckoutSource
            && $context->getEntity()->getSource()->getEntity() instanceof CheckoutLineItemsHolderInterface
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
