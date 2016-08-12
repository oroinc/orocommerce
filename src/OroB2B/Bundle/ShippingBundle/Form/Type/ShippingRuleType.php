<?php

namespace OroB2B\Bundle\ShippingBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\CurrencyBundle\Form\Type\CurrencySelectionType;
use Oro\Bundle\FormBundle\Form\Type\CollectionType;

use OroB2B\Bundle\ShippingBundle\Entity\ShippingRule;
use Symfony\Component\Validator\Constraints\Valid;

class ShippingRuleType extends AbstractType
{
    const NAME = 'orob2b_shipping_rule';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add('name', TextType::class, ['label' => 'orob2b.shipping.shippingrule.name.label'])
            ->add('enabled', CheckboxType::class, ['label' => 'orob2b.shipping.shippingrule.enabled.label'])
            ->add('priority', NumberType::class, ['label' => 'orob2b.shipping.shippingrule.priority.label'])
            ->add('currency', CurrencySelectionType::NAME, [
                'label' => 'orob2b.shipping.shippingrule.currency.label',
                'empty_value' => 'oro.currency.currency.form.choose',
            ])
            ->add('destinations', CollectionType::NAME, [
                'required' => false,
                'entry_type' => ShippingRuleDestinationType::NAME,
                'label' => 'orob2b.shipping.shippingrule.shipping_destinations.label',
            ])
            ->add('conditions', TextareaType::class, [
                'required' => false,
                'label' => 'orob2b.shipping.shippingrule.conditions.label',
            ])
            ->add('configurations', ShippingRuleConfigurationCollectionType::NAME, [
                'required' => false,
                'entry_type' => ShippingRuleConfigurationType::NAME,
                'label' => 'orob2b.shipping.shippingrule.configurations.label',
            ])
            ->add('stopProcessing', CheckboxType::class, [
                'required' => false,
                'label' => 'orob2b.shipping.shippingrule.stop_processing.label',
            ]);
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
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }
}
