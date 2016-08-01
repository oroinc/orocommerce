<?php

namespace OroB2B\Bundle\ShippingBundle\Form\EventSubscriber;

use Doctrine\Common\Collections\Collection;

use Symfony\Bridge\Doctrine\RegistryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

use OroB2B\Bundle\ShippingBundle\Entity\ShippingRuleConfiguration;
use OroB2B\Bundle\ShippingBundle\Method\ShippingMethodRegistry;

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
     * @var \Doctrine\Common\Persistence\ObjectManager|null
     */
    protected $manager;

    /**
     * @param FormFactoryInterface $factory
     * @param ShippingMethodRegistry $methodRegistry
     * @param RegistryInterface $doctrine
     */
    public function __construct(
        FormFactoryInterface $factory,
        ShippingMethodRegistry $methodRegistry,
        RegistryInterface $doctrine
    ) {
        $this->factory = $factory;
        $this->methodRegistry = $methodRegistry;
        $this->manager = $doctrine->getManagerForClass('OroB2BShippingBundle:FlatRateRuleConfiguration');
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

        $existingConfigs = [];
        foreach ($data as $index => $ruleConfiguration) {
            $method = $this->methodRegistry->getShippingMethod($ruleConfiguration->getMethod());
            if ($method) {
                $existingConfigs[$method->getFormType()][$ruleConfiguration->getType()] = $ruleConfiguration;
                $form->remove($index);
            }
        }
        $data->clear();

        foreach ($this->methodRegistry->getShippingMethods() as $method) {
            $methods = $method->getTypes();
            if (count($methods) === 0) {
                $methods = [$method->getName()];
            }
            foreach ($methods as $type) {
                $formName = $method->getFormType();
                $formData = null;
                if (array_key_exists($formName, $existingConfigs)
                    && array_key_exists($type, $existingConfigs[$formName])
                ) {
                    $formData = $existingConfigs[$formName][$type];
                }
                $childForm = $this->factory->createNamed(count($data), $formName, $formData, [
                    'auto_initialize' => false
                ]);
                if ($formData === null) {
                    $class = $childForm->getConfig()->getDataClass();
                    /** @var ShippingRuleConfiguration $formData */
                    $formData = new $class;
                    $formData->setType($type)
                        ->setMethod($method->getName())
                        ->setRule($form->getParent()->getData());
                    $childForm->setData($formData);
                }
                $form->add($childForm);
                $data->add($formData);
            }
        }
        $event->setData($data);
    }
}
