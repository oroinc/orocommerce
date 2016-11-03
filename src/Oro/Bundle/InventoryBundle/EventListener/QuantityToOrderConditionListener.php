<?php

namespace Oro\Bundle\InventoryBundle\EventListener;

use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutSource;
use Oro\Bundle\CheckoutBundle\Event\CheckoutValidateEvent;
use Oro\Bundle\InventoryBundle\Validator\QuantityToOrderValidatorService;
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
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param QuantityToOrderValidatorService $validatorService
     * @param TranslatorInterface $translator
     */
    public function __construct(
        QuantityToOrderValidatorService $validatorService,
        TranslatorInterface $translator
    ) {
        $this->validatorService = $validatorService;
        $this->translator = $translator;
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
    public function onCreateOrderCheck(ExtendableConditionEvent $event)
    {
        $context = $event->getContext();
        if (!$context instanceof ActionData || !$context->getEntity() instanceof ShoppingList) {
            return;
        }

        if (false == $this->validatorService->isLineItemListValid($context->getEntity()->getLineItems())) {
            $event->addError(self::QUANTITY_CHECK_ERROR, $context);
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
     * @param mixed $context
     * @return bool
     */
    protected function isNotCorrectConditionContext($context)
    {
        return (!$context instanceof WorkflowItem
            || !in_array($context->getWorkflowName(), self::$allowedWorkflows)
            || !$context->getEntity() instanceof Checkout
            || !$context->getEntity()->getSource() instanceof CheckoutSource
            // make sure checkout only done from shopping list
            || !$context->getEntity()->getSource()->getEntity() instanceof ShoppingList
            || !$context->getEntity()->getSource()->getShoppingList() instanceof ShoppingList
            || $context->getEntity()->getSource()->getQuoteDemand() instanceof QuoteDemand
        );
    }
}
