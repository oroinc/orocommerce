<?php

namespace Oro\Bundle\ShippingBundle\Form\EventSubscriber;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ShippingBundle\Entity\ShippingRuleMethodConfig;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodRegistry;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class RuleMethodConfigCollectionSubscriber implements EventSubscriberInterface
{
    /**
     * @var ShippingMethodRegistry
     */
    protected $methodRegistry;

    /**
     * @param ShippingMethodRegistry $methodRegistry
     */
    public function __construct(ShippingMethodRegistry $methodRegistry)
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
        /** @var Collection|ShippingRuleMethodConfig[] $data */
        $data = $event->getData();
        $form = $event->getForm();

        if (!$data) {
            return;
        }

        foreach ($data as $index => $methodConfig) {
            $shippingMethod = $this->methodRegistry->getShippingMethod($methodConfig->getMethod());
            if (!$shippingMethod) {
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
            if (array_key_exists('method', $itemData)
                && $this->methodRegistry->getShippingMethod($itemData['method']) !== null
            ) {
                $filteredSubmittedData[$index] = $itemData;
            } else {
                $form->remove($index);
            }
        }

        $event->setData($filteredSubmittedData);
    }
}
