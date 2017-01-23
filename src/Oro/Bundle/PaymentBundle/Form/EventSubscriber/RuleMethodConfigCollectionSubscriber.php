<?php

namespace Oro\Bundle\PaymentBundle\Form\EventSubscriber;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodConfig;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\Method\Provider\Registry\PaymentMethodProvidersRegistryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class RuleMethodConfigCollectionSubscriber implements EventSubscriberInterface
{
    /**
     * @var PaymentMethodProvidersRegistryInterface
     */
    protected $methodRegistry;

    /**
     * @param PaymentMethodProvidersRegistryInterface $methodRegistry
     */
    public function __construct(PaymentMethodProvidersRegistryInterface $methodRegistry)
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
            if (!$this->getPaymentMethodForConfig($methodConfig->getType())) {
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
            if (array_key_exists('type', $itemData) && $this->getPaymentMethodForConfig($itemData['type']) !== null) {
                $filteredSubmittedData[$index] = $itemData;
            } else {
                $form->remove($index);
            }
        }

        $event->setData($filteredSubmittedData);
    }

    /**
     * @param string $methodConfigType
     * @return PaymentMethodInterface|null
     */
    protected function getPaymentMethodForConfig($methodConfigType)
    {
        try {
            foreach ($this->methodRegistry->getPaymentMethodProviders() as $provider) {
                if ($provider->hasPaymentMethod($methodConfigType)) {
                    return $provider->getPaymentMethod($methodConfigType);
                }
            }
            return null;
        } catch (\InvalidArgumentException $e) {
            return null;
        }
    }
}
