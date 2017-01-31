<?php

namespace Oro\Bundle\InventoryBundle\EventListener;

use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutSource;
use Oro\Bundle\CheckoutBundle\Event\CheckoutValidateEvent;
use Oro\Bundle\InventoryBundle\Validator\QuantityToOrderValidatorService;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Event\QuickAddRowCollectionValidateEvent;
use Oro\Bundle\ProductBundle\Model\QuickAddRow;
use Oro\Bundle\ProductBundle\Model\QuickAddRowCollection;
use Oro\Bundle\SaleBundle\Entity\QuoteDemand;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Exception\InvalidTransitionException;
use Oro\Component\Action\Event\ExtendableConditionEvent;

class QuantityToOrderConditionListener
{
    const QUANTITY_CHECK_ERROR = 'quantity_check_error';

    /**
     * @var array
     */
    public static $allowedWorkflows = ['b2b_flow_checkout', 'b2b_flow_alternative_checkout'];

    /**
     * @var QuantityToOrderValidatorService
     */
    protected $validatorService;

    /**
     * @param QuantityToOrderValidatorService $validatorService
     */
    public function __construct(QuantityToOrderValidatorService $validatorService)
    {
        $this->validatorService = $validatorService;
    }

    /**
     * @param CheckoutValidateEvent $event
     * @throws InvalidTransitionException
     */
    public function onCheckoutValidate(CheckoutValidateEvent $event)
    {
        $workflowItem = $event->getContext();
        if ($this->isNotCorrectConditionContext($workflowItem)) {
            return;
        }

        /** @var ShoppingList $shoppingList */
        $shoppingList = $workflowItem->getEntity()->getSource()->getShoppingList();

        if (false == $this->validatorService->isLineItemListValid($shoppingList->getLineItems())) {
            $event->setIsCheckoutRestartRequired(true);
        }
    }

    /**
     * @param ExtendableConditionEvent $event
     */
    public function onStartCheckoutConditionCheck(ExtendableConditionEvent $event)
    {
        /** @var ActionData $context */
        $context = $event->getContext();
        if ($this->isNotCorrectConditionContextForStart($context)) {
            return;
        }

        /** @var Checkout $checkout */
        $checkout = $context->get('checkout');
        if (false == $this->validatorService->isLineItemListValid($checkout->getLineItems())) {
            $event->addError('oro.inventory.frontend.messages.quantity_limits_error');
        }
    }

    /**
     * @param ExtendableConditionEvent $event
     */
    public function onCheckoutConditionCheck(ExtendableConditionEvent $event)
    {
        $context = $event->getContext();
        if ($this->isNotCorrectConditionContext($context)) {
            return;
        }

        if (false == $this->validatorService->isLineItemListValid($context->getEntity()->getLineItems())) {
            $event->addError(self::QUANTITY_CHECK_ERROR, $context);
        }
    }

    /**
     * @param QuickAddRowCollectionValidateEvent $event
     */
    public function onQuickAddRowCollectionValidate(QuickAddRowCollectionValidateEvent $event)
    {
        $collection = $event->getQuickAddRowCollection();
        if (!$collection instanceof QuickAddRowCollection) {
            return;
        }

        /** @var QuickAddRow $quickAddRow */
        foreach ($collection as $quickAddRow) {
            $product = $quickAddRow->getProduct();
            if (!$product instanceof Product) {
                continue;
            }

            if ($maxError = $this->validatorService->getMaximumErrorIfInvalid($product, $quickAddRow->getQuantity())) {
                $quickAddRow->addError($maxError, ['allowedRFP' => true]);
                continue;
            }

            if ($minError = $this->validatorService->getMinimumErrorIfInvalid($product, $quickAddRow->getQuantity())) {
                $quickAddRow->addError($minError, ['allowedRFP' => true]);
            }
        }
    }

    /**
     * @param mixed $context
     * @return bool
     */
    protected function isNotCorrectConditionContext($context)
    {
        return (!$context instanceof WorkflowItem
            || !in_array($context->getWorkflowName(), self::$allowedWorkflows, true)
            || !$context->getEntity() instanceof Checkout
            || !$context->getEntity()->getSource() instanceof CheckoutSource
            // make sure checkout only done from shopping list
            || !$context->getEntity()->getSource()->getEntity() instanceof ShoppingList
            || !$context->getEntity()->getSource()->getShoppingList() instanceof ShoppingList
            || $context->getEntity()->getSource()->getQuoteDemand() instanceof QuoteDemand
        );
    }

    /**
     * @param mixed $context
     * @return bool
     */
    protected function isNotCorrectConditionContextForStart($context)
    {
        return (!$context instanceof ActionData || !is_a($context->get('checkout'), Checkout::class, true));
    }
}
