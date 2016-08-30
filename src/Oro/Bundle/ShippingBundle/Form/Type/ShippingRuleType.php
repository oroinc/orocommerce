<?php

namespace Oro\Bundle\ShippingBundle\Form\Type;

use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;
use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Bundle\ShippingBundle\Entity\ShippingRule;
use Oro\Bundle\ShippingBundle\Entity\ShippingRuleMethodConfig;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodRegistry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ShippingRuleType extends AbstractType
{
    const BLOCK_PREFIX = 'oro_shipping_rule';

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
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name', TextareaType::class, ['label' => 'oro.shipping.shippingrule.name.label'])
            ->add('enabled', CheckboxType::class, ['label' => 'oro.shipping.shippingrule.enabled.label'])
            ->add('priority', NumberType::class, ['label' => 'oro.shipping.shippingrule.priority.label'])
            ->add('currency', CurrencySelectionType::class, [
                'label' => 'oro.shipping.shippingrule.currency.label',
                'empty_value' => 'oro.currency.currency.form.choose',
            ])
            ->add('destinations', CollectionType::class, [
                'required' => false,
                'entry_type' => ShippingRuleDestinationType::class,
                'label' => 'oro.shipping.shippingrule.shipping_destinations.label',
            ])
            ->add('conditions', TextareaType::class, [
                'required' => false,
                'label' => 'oro.shipping.shippingrule.conditions.label',
            ])
            ->add('methodConfigs', ShippingRuleMethodConfigCollectionType::class, [
                'required' => false,
            ])
            ->add('stopProcessing', CheckboxType::class, [
                'required' => false,
                'label' => 'oro.shipping.shippingrule.stop_processing.label',
            ]);

        $builder->addEventListener(FormEvents::POST_SET_DATA, [$this, 'postSet']);
        $builder->addEventListener(FormEvents::PRE_SUBMIT, [$this, 'preSubmit']);
    }

    /**
     * @param FormEvent $event
     */
    public function postSet(FormEvent $event)
    {
        /** @var ShippingRule $data */
        $data = $event->getData();
        $form = $event->getForm();
        if (!$data) {
            return;
        }

        $methods = $this->getMethods(array_map(function (ShippingRuleMethodConfig $config) {
            return $config->getMethod();
        }, $data->getMethodConfigs()->toArray()));

        if ($methods) {
            $form->add('method', ChoiceType::class, [
                'mapped' => false,
                'choices' => $methods,
            ]);
        }
    }

    /**
     * @param FormEvent $event
     */
    public function preSubmit(FormEvent $event)
    {
        $submittedData = $event->getData();
        $form = $event->getForm();
        if (!$event->getData()) {
            return;
        }

        $methods = $this->getMethods(array_map(function ($methodConfigs) {
            return $methodConfigs['method'];
        }, $submittedData['methodConfigs']));

        $form->remove('method');
        if ($methods) {
            $form->add('method', ChoiceType::class, [
                'mapped' => false,
                'choices' => $methods,
            ]);
        }
    }

    /**
     * @param OptionsResolver $resolver
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => ShippingRule::class,
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::BLOCK_PREFIX;
    }

    /**
     * @param array $configuredMethods
     * @return array
     */
    protected function getMethods(array $configuredMethods = [])
    {
        return array_diff_key(array_reduce(
            $this->methodRegistry->getShippingMethods(),
            function (array $result, ShippingMethodInterface $method) {
                $result[$method->getIdentifier()] = $method->getLabel();
                return $result;
            },
            []
        ), array_flip($configuredMethods));
    }
}
