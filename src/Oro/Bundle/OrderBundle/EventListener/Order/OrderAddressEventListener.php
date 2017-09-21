<?php

namespace Oro\Bundle\OrderBundle\EventListener\Order;

use Oro\Bundle\AddressBundle\Entity\AddressType;
use Oro\Bundle\OrderBundle\Event\OrderEvent;

/**
 * This class adds to the events blocks, details about address blocks taking into account submission.
 */
class OrderAddressEventListener extends AbstractFormEventListener
{
    /**
     * {@inheritdoc}
     */
    public function onOrderEvent(OrderEvent $event)
    {
        if (null === $event->getSubmittedData()) {
            return;
        }

        $orderForm = $event->getForm();
        foreach ([AddressType::TYPE_BILLING, AddressType::TYPE_SHIPPING] as $type) {
            $fieldName = sprintf('%sAddress', $type);
            if ($orderForm->has($fieldName)) {
                $form = $this->createFieldWithSubmission($orderForm, $fieldName, $event->getSubmittedData());
                $view = $this->renderForm(
                    $form->get($fieldName)->createView(),
                    'OroOrderBundle:Form:customerAddressSelector.html.twig'
                );
                $event->getData()->offsetSet($fieldName, $view);
            }
        }
    }
}
