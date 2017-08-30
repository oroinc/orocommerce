<?php

namespace Oro\Bundle\PromotionBundle\EventListener;

use Oro\Bundle\OrderBundle\Event\OrderEvent;
use Oro\Bundle\OrderBundle\EventListener\Order\AbstractFormEventListener;

/**
 * Listener renders applied promotion collection form by given data on entry point call
 */
class OrderAppliedPromotionEventListener extends AbstractFormEventListener
{
    /**
     * {@inheritdoc}
     */
    public function onOrderEvent(OrderEvent $event)
    {
        $orderForm = $event->getForm();
        if ($orderForm->has('appliedPromotions') && $event->getSubmittedData()) {
            $form = $this->formFactory->create(
                $orderForm->getConfig()->getType()->getName(),
                $event->getOrder()
            );

            $view = $this->renderForm(
                $form->createView(),
                'OroPromotionBundle:Order:applied_promotions.html.twig'
            );
            $event->getData()->offsetSet('appliedPromotions', $view);
        }
    }
}
