<?php

namespace Oro\Bundle\ShippingBundle\Form\Type;

use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;
use Oro\Bundle\FormBundle\Form\Type\CollectionType;
use Oro\Bundle\ShippingBundle\Entity\ShippingRule;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodInterface;
use Oro\Bundle\ShippingBundle\Method\ShippingMethodRegistry;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;

class ShippingRuleType extends AbstractType
{
    const BLOCK_PREFIX = 'oro_shipping_rule';

    /**
     * @var ShippingMethodRegistry
     */
    protected $methodRegistry;

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @param ShippingMethodRegistry $methodRegistry
     * @param TranslatorInterface $translator
     */
    public function __construct(ShippingMethodRegistry $methodRegistry, TranslatorInterface $translator)
    {
        $this->methodRegistry = $methodRegistry;
        $this->translator = $translator;
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
            ])
            ->add('method', ChoiceType::class, [
                'mapped' => false,
                'choices' => $this->getMethods(),
            ]);
    }

    /**
     * {@inheritdoc}
     */
    public function buildView(FormView $view, FormInterface $form, array $options)
    {
        $view->vars['methods'] = $this->getMethods(true);
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
     * @param bool $translate
     * @return array
     */
    protected function getMethods($translate = false)
    {
        return array_reduce(
            $this->methodRegistry->getShippingMethods(),
            function (array $result, ShippingMethodInterface $method) use ($translate) {
                $label = $method->getLabel();
                if ($translate) {
                    $label = $this->translator->trans($label);
                }
                $result[$method->getIdentifier()] = $label;
                return $result;
            },
            []
        );
    }
}
