<?php

namespace Oro\Bundle\ShippingBundle\Form\EventSubscriber;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class DestinationCollectionTypeSubscriber implements EventSubscriberInterface
{
    const NOT_VALID_VALUE = 0;

    /**
     * {@inheritdoc}
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::PRE_SUBMIT => 'preSubmit'
        ];
    }

    /**
     * @param FormEvent $event
     */
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
            if ($destination && array_key_exists('country', $destination) && !$destination['country']) {
                $data['destinations'][$index]['country'] = self::NOT_VALID_VALUE;
            }
        }

        $event->setData($data);
    }
}
