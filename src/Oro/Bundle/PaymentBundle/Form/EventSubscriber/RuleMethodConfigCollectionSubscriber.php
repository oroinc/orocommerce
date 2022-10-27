<?php

namespace Oro\Bundle\PaymentBundle\Form\EventSubscriber;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\PaymentBundle\Entity\PaymentMethodConfig;
use Oro\Bundle\PaymentBundle\Method\PaymentMethodInterface;
use Oro\Bundle\PaymentBundle\Method\Provider\PaymentMethodProviderInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;

class RuleMethodConfigCollectionSubscriber implements EventSubscriberInterface
{
    /**
     * @var PaymentMethodProviderInterface
     */
    protected $paymentMethodProvider;

    public function __construct(PaymentMethodProviderInterface $paymentMethodProvider)
    {
        $this->paymentMethodProvider = $paymentMethodProvider;
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
     *
     * @return PaymentMethodInterface|null
     */
    protected function getPaymentMethodForConfig($methodConfigType)
    {
        $paymentMethod = null;
        if ($this->paymentMethodProvider->hasPaymentMethod($methodConfigType)) {
            $paymentMethod = $this->paymentMethodProvider->getPaymentMethod($methodConfigType);
        }

        return $paymentMethod;
    }
}
