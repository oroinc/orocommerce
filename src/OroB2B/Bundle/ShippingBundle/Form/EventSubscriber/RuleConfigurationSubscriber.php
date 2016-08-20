<?php

namespace Oro\Bundle\ShippingBundle\Form\EventSubscriber;

use Doctrine\Common\Collections\Collection;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

use Oro\Bundle\ShippingBundle\Entity\ShippingRuleConfiguration;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodRegistry;

class RuleConfigurationSubscriber implements EventSubscriberInterface
{
    /**
     * @var FormFactoryInterface
     */
    protected $factory;

    /**
     * @var ShippingMethodRegistry
     */
    protected $methodRegistry;

    /**
     * @param FormFactoryInterface $factory
     * @param ShippingMethodRegistry $methodRegistry
     */
    public function __construct(FormFactoryInterface $factory, ShippingMethodRegistry $methodRegistry)
    {
        $this->factory = $factory;
        $this->methodRegistry = $methodRegistry;
    }

    /**
     * @return array
     */
    public static function getSubscribedEvents()
    {
        return [
            FormEvents::PRE_SET_DATA => 'preSet',
        ];
    }

    /**
     * @param FormEvent $event
     */
    public function preSet(FormEvent $event)
    {
        /** @var FormInterface|Form[] $form */
        $form = $event->getForm();
        /** @var Collection|ShippingRuleConfiguration[] $data */
        $data = $event->getData();

        if (!$data) {
            return;
        }

        $existingConfigs = [];
        foreach ($data as $index => $ruleConfiguration) {
            $method = $this->methodRegistry->getShippingMethod($ruleConfiguration->getMethod());
            if ($method) {
                $existingConfigs[$method->getFormType()][$ruleConfiguration->getType()] = $ruleConfiguration;
                $form->remove($index);
            }
        }
        $data->clear();

        $index = 0;
        foreach ($this->methodRegistry->getShippingMethods() as $method) {
            $types = $method->getShippingTypes();
            if (count($types) === 0) {
                $types = [$method->getName()];
            }
            $formName = $method->getFormType();
            foreach ($types as $type) {
                if (array_key_exists($formName, $existingConfigs)
                    && array_key_exists($type, $existingConfigs[$formName])
                ) {
                    $formData = $existingConfigs[$formName][$type];
                } else {
                    $class = $method->getRuleConfigurationClass();
                    /** @var ShippingRuleConfiguration $formData */
                    $formData = new $class;
                    $formData->setType($type)
                        ->setMethod($method->getName())
                        ->setRule($form->getParent()->getData());
                }
                $childForm = $this->factory->createNamed($index, $formName, null, [
                    'auto_initialize' => false
                ]);
                $form->add($childForm);
                $data->set($index, $formData);
                $index++;
            }
        }
        $event->setData($data);
    }
}
