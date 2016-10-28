<?php

namespace Oro\Bundle\WarehouseBundle\EventListener;

use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutSource;
use Oro\Bundle\CheckoutBundle\Event\CheckoutValidateEvent;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\WarehouseBundle\Validator\QuantityToOrderValidator;
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
     * @var QuantityToOrderValidator
     */
    protected $quantityValidator;

    /**
     * @var SessionInterface
     */
    protected $session;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * QuantityToOrderConditionListener constructor.
     *
     * @param QuantityToOrderValidator $validator
     * @param Session|SessionInterface $session
     * @param TranslatorInterface $translator
     */
    public function __construct(QuantityToOrderValidator $validator, Session $session, TranslatorInterface $translator)
    {
        $this->quantityValidator = $validator;
        $this->session = $session;
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

        if (false == $this->quantityValidator->isLineItemListValid($shoppingList->getLineItems())) {
            $event->setIsCheckoutRestartRequired(true);
        }
    }

    /**
     * @param ExtendableConditionEvent $event
     */
    public function onCreateOrderCheck(ExtendableConditionEvent $event)
    {
        $context = $event->getContext();
        if (!$context instanceof ActionData
            || !$context->getEntity() instanceof ShoppingList
        ) {
            return;
        }

        if (false == $this->quantityValidator->isLineItemListValid($context->getEntity()->getLineItems())) {
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

        if (false == $this->quantityValidator->isLineItemListValid($context->getEntity()->getLineItems())) {
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
            || !$context->getEntity()->getSource()->getShoppingList() instanceof ShoppingList);
    }
}
