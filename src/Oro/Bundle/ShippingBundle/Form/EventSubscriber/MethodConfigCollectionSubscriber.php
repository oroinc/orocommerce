<?php

namespace Oro\Bundle\ShippingBundle\Form\EventSubscriber;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ShippingBundle\Entity\ShippingMethodConfig;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodProviderInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

/**
 * Form event subscriber that filters shipping method configurations based on method availability.
 *
 * This subscriber removes shipping method configurations from the form when the corresponding shipping method
 * is no longer available in the system, ensuring that only valid methods can be configured in shipping rules.
 */
class MethodConfigCollectionSubscriber implements EventSubscriberInterface
{
    /**
     * @var ShippingMethodProviderInterface
     */
    protected $shippingMethodProvider;

    public function __construct(ShippingMethodProviderInterface $shippingMethodProvider)
    {
        $this->shippingMethodProvider = $shippingMethodProvider;
    }

    /**
     * @return array
     */
    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::PRE_SET_DATA => 'preSet',
            FormEvents::PRE_SUBMIT => 'preSubmit',
        ];
    }

    public function preSet(FormEvent $event)
    {
        /** @var Collection|ShippingMethodConfig[] $data */
        $data = $event->getData();
        $form = $event->getForm();

        if (!$data) {
            return;
        }

        foreach ($data as $index => $methodConfig) {
            $shippingMethod = $this->shippingMethodProvider->getShippingMethod($methodConfig->getMethod());
            if (!$shippingMethod) {
                $data->remove($index);
                $form->remove($index);
            }
        }
        $event->setData($data);
    }

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
                && $this->shippingMethodProvider->getShippingMethod($itemData['method']) !== null
            ) {
                $filteredSubmittedData[$index] = $itemData;
            } else {
                $form->remove($index);
            }
        }

        $event->setData($filteredSubmittedData);
    }
}
