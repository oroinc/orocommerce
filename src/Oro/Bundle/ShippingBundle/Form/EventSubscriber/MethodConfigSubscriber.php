<?php

namespace Oro\Bundle\ShippingBundle\Form\EventSubscriber;

use Oro\Bundle\ShippingBundle\Entity\ShippingMethodConfig;
use Oro\Bundle\ShippingBundle\Form\Type\ShippingMethodTypeConfigCollectionType;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodProviderInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

/**
 * Handles form events to dynamically recreate shipping method configuration fields based on the selected method.
 */
class MethodConfigSubscriber implements EventSubscriberInterface
{
    /**
     * @var FormFactoryInterface
     */
    protected $factory;

    /**
     * @var ShippingMethodProviderInterface
     */
    protected $shippingMethodProvider;

    public function __construct(FormFactoryInterface $factory, ShippingMethodProviderInterface $shippingMethodProvider)
    {
        $this->factory = $factory;
        $this->shippingMethodProvider = $shippingMethodProvider;
    }

    /**
     * @return array
     */
    #[\Override]
    public static function getSubscribedEvents(): array
    {
        return [
            FormEvents::POST_SET_DATA => 'postSetData',
            FormEvents::PRE_SUBMIT => 'preSubmit',
        ];
    }

    public function postSetData(FormEvent $event): void
    {
        /** @var ShippingMethodConfig $data */
        $data = $event->getData();
        if (!$data) {
            return;
        }
        $this->recreateDynamicChildren($event->getForm(), $data->getMethod());
    }

    public function preSubmit(FormEvent $event): void
    {
        $submittedData = $event->getData();
        $form = $event->getForm();
        /** @var ShippingMethodConfig $data */
        $data = $form->getData();

        if (!$data) {
            $this->recreateDynamicChildren($form, $submittedData['method']);
            $event->setData($submittedData);
        }
    }

    /**
     * @param FormInterface $form
     * @param string $method
     */
    protected function recreateDynamicChildren(FormInterface $form, $method): void
    {
        $shippingMethod = $this->shippingMethodProvider->getShippingMethod($method);
        $oldOptions = $form->get('typeConfigs')->getConfig()->getOptions();
        $form->add('typeConfigs', ShippingMethodTypeConfigCollectionType::class, array_merge($oldOptions, [
            'is_grouped' => $shippingMethod->isGrouped(),
        ]));

        $oldOptions = $form->get('options')->getConfig()->getOptions();
        $child = $this->factory->createNamed('options', $shippingMethod->getOptionsConfigurationFormType());
        $form->add('options', $shippingMethod->getOptionsConfigurationFormType(), array_merge($oldOptions, [
            'compound' => $child->getConfig()->getOptions()['compound']
        ]));
    }
}
