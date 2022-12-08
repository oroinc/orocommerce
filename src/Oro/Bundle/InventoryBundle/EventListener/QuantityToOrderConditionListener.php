<?php

namespace Oro\Bundle\InventoryBundle\EventListener;

use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Event\CheckoutValidateEvent;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\InventoryBundle\Validator\QuantityToOrderValidatorService;
use Oro\Bundle\ProductBundle\Model\ProductLineItemsHolderInterface;
use Oro\Bundle\SaleBundle\Entity\QuoteDemand;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\WorkflowBundle\Entity\WorkflowItem;
use Oro\Bundle\WorkflowBundle\Exception\InvalidTransitionException;
use Oro\Component\Action\Event\ExtendableConditionEvent;
use Oro\Component\Checkout\Entity\CheckoutSourceEntityInterface;

/**
 * Handles line items inventory validation events.
 */
class QuantityToOrderConditionListener
{
    /** @var string */
    const QUANTITY_CHECK_ERROR = 'quantity_check_error';

    /** @var array */
    protected const ALLOWED_WORKFLOWS = [
        'b2b_flow_checkout',
        'b2b_flow_checkout_single_page',
    ];

    /** @var QuantityToOrderValidatorService */
    private $validatorService;

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /** @var array */
    private $localCache = [];

    public function __construct(QuantityToOrderValidatorService $validatorService, DoctrineHelper $doctrineHelper)
    {
        $this->validatorService = $validatorService;
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * @throws InvalidTransitionException
     */
    public function onCheckoutValidate(CheckoutValidateEvent $event)
    {
        $workflowItem = $event->getContext();
        if ($this->isNotCorrectConditionContext($workflowItem)) {
            return;
        }

        /** @var Checkout $checkout */
        $checkout = $workflowItem->getEntity();
        if (!$this->isLineItemListValid($checkout, $checkout->getSourceEntity())) {
            $event->setIsCheckoutRestartRequired(true);
        }
    }

    public function onStartCheckoutConditionCheck(ExtendableConditionEvent $event)
    {
        /** @var ActionData $context */
        $context = $event->getContext();
        if (!$this->isApplicableContextForStartCheckout($context)) {
            return;
        }

        /** @var Checkout $checkout */
        $checkout = $context->get('checkout');
        if (!$this->isLineItemListValid($checkout, $checkout->getSourceEntity())) {
            $event->addError('oro.inventory.frontend.messages.quantity_limits_error');
        }
    }

    /**
     * Event listener to check if shopping list actions can be run (ex. used to show/hide shopping list trigger buttons)
     */
    public function onShoppingListStart(ExtendableConditionEvent $event)
    {
        $context = $event->getContext();
        if (!$context instanceof WorkflowItem
            || !in_array($context->getWorkflowName(), static::ALLOWED_WORKFLOWS, true)
            || !$context->getResult()->get('shoppingList') instanceof ShoppingList
        ) {
            return;
        }

        /** @var ShoppingList $shoppingList */
        $shoppingList = $context->getResult()->get('shoppingList');
        if (!$this->isLineItemListValid($shoppingList, $shoppingList)) {
            $event->addError('');
        }
    }

    public function onCheckoutConditionCheck(ExtendableConditionEvent $event)
    {
        $context = $event->getContext();
        if ($this->isNotCorrectConditionContext($context)) {
            return;
        }

        /** @var Checkout $checkout */
        $checkout = $context->getEntity();
        if (!$this->isLineItemListValid($checkout, $checkout->getSourceEntity())) {
            $event->addError(self::QUANTITY_CHECK_ERROR, $context);
        }
    }

    /**
     * @param mixed $context
     * @return bool
     */
    protected function isNotCorrectConditionContext($context)
    {
        return (!$context instanceof WorkflowItem
            || !in_array($context->getWorkflowName(), static::ALLOWED_WORKFLOWS, true)
            || !$context->getEntity() instanceof Checkout
            // make sure that checkout not done from quote demand
            || $context->getEntity()->getSourceEntity() instanceof QuoteDemand
        );
    }

    /**
     * @param mixed $context
     * @return bool
     */
    protected function isApplicableContextForStartCheckout($context)
    {
        if (!$context instanceof ActionData) {
            return false;
        }

        $checkout = $context->get('checkout');

        return ($checkout instanceof Checkout && !$checkout->getSourceEntity() instanceof QuoteDemand);
    }

    private function isLineItemListValid(
        ProductLineItemsHolderInterface $holder,
        ?CheckoutSourceEntityInterface $sourceEntity
    ): bool {
        $lineItems = $holder->getLineItems();
        if (!$sourceEntity) {
            return $this->validatorService->isLineItemListValid($lineItems);
        }

        $key = sprintf(
            '%s|%s|%s',
            count($lineItems),
            $this->doctrineHelper->getClass($sourceEntity),
            $sourceEntity->getSourceDocumentIdentifier()
        );

        if (!array_key_exists($key, $this->localCache)) {
            $this->localCache[$key] = $this->validatorService->isLineItemListValid($lineItems);
        }

        return $this->localCache[$key];
    }
}
