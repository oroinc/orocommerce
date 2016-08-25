<?php

namespace Oro\Bundle\ShippingBundle\Form\EventSubscriber;

use Doctrine\Common\Collections\Collection;
use Oro\Bundle\ShippingBundle\Entity\ShippingRuleMethodTypeConfig;
use Oro\Bundle\ShippingBundle\Form\Type\ShippingRuleMethodTypeConfigType;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodRegistry;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Form\Form;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormFactoryInterface;
use Symfony\Component\Form\FormInterface;

class RuleMethodTypeConfigCollectionSubscriber implements EventSubscriberInterface
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
        /** @var Collection|ShippingRuleMethodTypeConfig[] $data */
        $data = $event->getData();

        if (!$data) {
            return;
        }

        $methodConfig = $form->getParent()->getData();
        $method = $this->methodRegistry->getShippingMethod($methodConfig->getMethod());

        $formType = ShippingRuleMethodTypeConfigType::class;
        $renderedTypes = [];
        foreach ($data as $index => $typeConfig) {
            $child = $form->get($index);
            $childOptions = $child->getConfig()->getOptions();
            $type = $method->getType($typeConfig->getType());
            $newChild = $this->factory->createNamed($index, $formType, null, array_merge($childOptions, [
                'options_type' => $type->getOptionsConfigurationFormType(),
                'is_grouped' => $method->isGrouped(),
                'label' => $type->getLabel(),
            ]));
            $renderedTypes[] = $type->getIdentifier();
            $form->remove($index);
            $form->add($newChild);
        }

        $index = count($data);
        foreach ($method->getTypes() as $type) {
            if (!in_array($type->getIdentifier(), $renderedTypes, true)) {
                $newChild = $this->factory->createNamed($index, $formType, null, [
                    'options_type' => $type->getOptionsConfigurationFormType(),
                    'is_grouped' => $method->isGrouped(),
                    'auto_initialize' => false,
                    'label' => $type->getLabel(),
                ]);
                $form->add($newChild);
                $entity = new ShippingRuleMethodTypeConfig();
                $entity->setType($type->getIdentifier())
                    ->setMethodConfig($methodConfig);
                $data->set($index, $entity);
                $index++;
            }
        }
    }
}
