<?php

namespace Oro\Bundle\PricingBundle\Api\Form;

use Oro\Bundle\PricingBundle\Entity\PriceListSchedule;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * This subscriber ensures that priceList is updated
 */
class AddSchedulesToPriceListApiFormSubscriber implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::SUBMIT => 'onSubmit'
        ];
    }

    /**
     * @param FormEvent $event
     */
    public function onSubmit(FormEvent $event)
    {
        if (!$event->getForm()->has('priceList')) {
            return;
        }

        $data = $event->getData();
        if (!$data instanceof PriceListSchedule) {
            return;
        }

        $priceList = $data->getPriceList();
        if (null === $priceList) {
            return;
        }

        $priceList->addSchedule($data);
    }
}
