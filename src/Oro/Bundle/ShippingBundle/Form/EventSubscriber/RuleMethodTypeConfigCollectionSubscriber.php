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
use Symfony\Component\Form\FormInterface;

class RuleMethodTypeConfigCollectionSubscriber implements EventSubscriberInterface
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
            $form->add($index, $formType, array_merge($childOptions, [
                'options_type' => $type->getOptionsConfigurationFormType(),
                'is_grouped' => $method->isGrouped(),
                'label' => $type->getLabel(),
            ]));
            $renderedTypes[] = $type->getIdentifier();
        }

        $index = count($data);
        foreach ($method->getTypes() as $type) {
            if (!in_array($type->getIdentifier(), $renderedTypes, true)) {
                $form->add($index, $formType, [
                    'options_type' => $type->getOptionsConfigurationFormType(),
                    'is_grouped' => $method->isGrouped(),
                    'auto_initialize' => false,
                    'label' => $type->getLabel(),
                ]);
                $entity = new ShippingRuleMethodTypeConfig();
                $entity->setType($type->getIdentifier())
                    ->setMethodConfig($methodConfig);
                $data->set($index, $entity);
                $index++;
            }
        }
    }

    /**
     * {@inheritdoc}
     */
    public function preSubmit(FormEvent $event)
    {
        /** @var array $submittedData */
        $submittedData = $event->getData();
        $form = $event->getForm();

        if (!$event->getData()) {
            return;
        }

        $methodIdentifier = $form->getParent()->get('method')->getData();
        $method = $this->methodRegistry->getShippingMethod($methodIdentifier);

        $formType = ShippingRuleMethodTypeConfigType::class;

        foreach ($submittedData as $index => $methodTypeData) {
            $child = $form->get($index);
            $childOptions = $child->getConfig()->getOptions();
            $type = $method->getType($methodTypeData['type']);
            $form->add($index, $formType, array_merge($childOptions, [
                'options_type' => $type->getOptionsConfigurationFormType(),
                'is_grouped' => $method->isGrouped(),
                'label' => $type->getLabel(),
            ]));
        }
    }
}
