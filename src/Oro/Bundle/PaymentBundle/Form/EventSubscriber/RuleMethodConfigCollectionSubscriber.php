<?php

namespace Oro\Bundle\PaymentBundle\Form\EventSubscriber;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodConfig;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodRegistry;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class RuleMethodConfigCollectionSubscriber implements EventSubscriberInterface
{
    /**
     * @var PaymentMethodRegistry
     */
    protected $methodRegistry;

    /**
     * @param PaymentMethodRegistry $methodRegistry
     */
    public function __construct(PaymentMethodRegistry $methodRegistry)
    {
        $this->methodRegistry = $methodRegistry;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::PRE_SET_DATA => 'preSet',
            FormEvents::PRE_SUBMIT => 'preSubmit',
        ];
    }

    /**
     * @param FormEvent $event
     */
    public function preSet(FormEvent $event)
    {
        /** @var Collection|PaymentMethodConfig[] $data */
        $data = $event->getData();
        $form = $event->getForm();

        if (!$data) {
            return;
        }

        foreach ($data as $index => $methodConfig) {
            try {
                $this->methodRegistry->getPaymentMethod($methodConfig->getType());
            } catch (\InvalidArgumentException $e) {
                $data->remove($index);
                $form->remove($index);
            }
        }
        $event->setData($data);
    }

    /**
     * @param FormEvent $event
     */
    public function preSubmit(FormEvent $event)
    {
        /** @var array $data */
        $submittedData = $event->getData();
        $form = $event->getForm();

        if (!$submittedData) {
            return;
        }

        $filteredSubmittedData = [];
        foreach ($submittedData as $index => $itemData) {
            try {
                $paymentMethod = $this->methodRegistry->getPaymentMethod($itemData['type']);
            } catch (\InvalidArgumentException $e) {
                $paymentMethod = null;
            }
            if (array_key_exists('type', $itemData) && $paymentMethod !== null) {
                $filteredSubmittedData[$index] = $itemData;
            } else {
                $form->remove($index);
            }
        }

        $event->setData($filteredSubmittedData);
    }
}
