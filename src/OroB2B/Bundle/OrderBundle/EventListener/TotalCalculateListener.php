<?php

namespace OroB2B\Bundle\OrderBundle\EventListener;

use Symfony\Component\Form\FormFactory;

use OroB2B\Bundle\OrderBundle\Entity\Order;
use OroB2B\Bundle\OrderBundle\Form\Type\OrderType;
use OroB2B\Bundle\PricingBundle\Event\TotalCalculateBeforeEvent;

class TotalCalculateListener
{
    /** @var FormFactory */
    protected $formFactory;

    /**
     * @param FormFactory $formFactory
     */
    public function __construct(FormFactory $formFactory)
    {
        $this->formFactory = $formFactory;
    }

    /**
     * @param TotalCalculateBeforeEvent $event
     */
    public function onBeforeTotalCalculate(TotalCalculateBeforeEvent $event)
    {
        /** @var Order $entity */
        $entity = $event->getEntity();
        $request = $event->getRequest();

        if ($entity instanceof Order) {
            $form = $this->formFactory->create(OrderType::NAME, $entity);
            $form->submit($request);
        }
    }
}
