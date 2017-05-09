<?php

namespace Oro\Bundle\OrderBundle\Api\Form\EventListener;

use Oro\Bundle\OrderBundle\Entity\OrderDiscount;
use Oro\Bundle\OrderBundle\Total\TotalHelper;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class DiscountListener implements EventSubscriberInterface
{
    /**
     * @var TotalHelper
     */
    private $totalHelper;

    /**
     * @param TotalHelper $totalHelper
     */
    public function __construct(TotalHelper $totalHelper)
    {
        $this->totalHelper = $totalHelper;
    }

    /**
     * {@inheritDoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::SUBMIT => 'onSubmit',
        ];
    }

    /**
     * @param FormEvent $event
     */
    public function onSubmit(FormEvent $event)
    {
        if (!$event->getForm()->has('order')) {
            return;
        }

        $data = $event->getData();
        if ($data instanceof OrderDiscount) {
            $order = $data->getOrder();
            if (null !== $order) {
                $order->addDiscount($data);
                $this->totalHelper->fillDiscounts($order);
            }
        }
    }
}
