<?php

namespace Oro\Bundle\PaymentBundle\Form\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class DestinationCollectionTypeSubscriber implements EventSubscriberInterface
{
    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::PRE_SUBMIT => 'preSubmit'
        ];
    }

    public function preSubmit(FormEvent $event)
    {
        $data = $event->getData();

        if (!$data ||
            !(is_array($data) && array_key_exists('destinations', $data)) ||
            !is_array($data['destinations'])
        ) {
            return;
        }

        foreach ($data['destinations'] as $index => $destination) {
            if (!array_filter($destination)) {
                unset($data['destinations']);
            }
        }

        $event->setData($data);
    }
}
