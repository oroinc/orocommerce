<?php

namespace Oro\Bundle\WarehouseBundle\EventListener;

use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Translation\TranslatorInterface;

use Oro\Bundle\ActionBundle\Model\ActionData;
use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\Entity\CheckoutSource;
use Oro\Bundle\ProductBundle\Entity\Product;
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
    public static $allowedWorkflows = ['b2b_flow_checkout'];

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
     * @param ExtendableConditionEvent $event
     * @throws InvalidTransitionException
     */
    public function onConditionAllowedCheck(ExtendableConditionEvent $event)
    {
        $workflowItem = $event->getContext();
        if (!$workflowItem instanceof WorkflowItem
            || !in_array($workflowItem->getWorkflowName(), self::$allowedWorkflows)
            || !$workflowItem->getEntity() instanceof Checkout
            || !$workflowItem->getEntity()->getSource() instanceof CheckoutSource
            // make sure checkout only done from shopping list
            || !$workflowItem->getEntity()->getSource()->getEntity() instanceof ShoppingList
            || !$workflowItem->getEntity()->getSource()->getShoppingList() instanceof ShoppingList
        ) {
            return;
        }

        /** @var ShoppingList $shoppingList */
        $shoppingList = $workflowItem->getEntity()->getSource()->getShoppingList();

        if (false == $this->quantityValidator->isLineItemListValid($shoppingList->getLineItems())) {
            throw InvalidTransitionException::workflowCanceledByTransition(
                $workflowItem->getWorkflowName(),
                $workflowItem->getCurrentStep() ? $workflowItem->getCurrentStep()->getName() : 'null'
            );
        }
    }

    /**
     * @param ExtendableConditionEvent $event
     */
    public function onProductQuantityCheck(ExtendableConditionEvent $event)
    {
        $context = $event->getContext();
        if (!$context instanceof ActionData
            || !$context->getEntity() instanceof ShoppingList
        ) {
            return;
        }

        if (false == $this->quantityValidator->isLineItemListValid($context->getEntity()->getLineItems())) {
            $event->addError(self::QUANTITY_CHECK_ERROR);
        }
    }

    /**
     * @param Product $product
     * @return string
     */
    protected function getFlashMessageDomain(Product $product)
    {
        return sprintf('product.%s', $product->getSku());
    }
}
