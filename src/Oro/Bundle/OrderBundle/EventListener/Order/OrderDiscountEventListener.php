<?php

namespace Oro\Bundle\OrderBundle\EventListener\Order;

use Oro\Bundle\OrderBundle\Event\OrderEvent;
use Oro\Bundle\OrderBundle\Form\Type\OrderType;

/**
 * This class adds to the event, block with discounts collection generated based on the submission.
 */
class OrderDiscountEventListener extends AbstractFormEventListener
{
    const TEMPLATE = '@OroOrder/Form/discountCollectionWidget.html.twig';

    /**
     * {@inheritdoc}
     */
    public function onOrderEvent(OrderEvent $event)
    {
        if (null === $event->getSubmittedData()) {
            return;
        }

        $orderForm = $event->getForm();
        $fieldName = OrderType::DISCOUNTS_FIELD_NAME;

        if ($orderForm->has($fieldName)) {
            $formView = $orderForm->createView();
            $formView->children['discounts']->vars['order'] = $orderForm->getData();

            $view = $this->renderForm($formView, self::TEMPLATE);
            $event->getData()->offsetSet($fieldName, $view);
        }
    }
}
