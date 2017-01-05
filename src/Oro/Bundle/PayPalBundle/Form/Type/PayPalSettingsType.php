<?php

namespace Oro\Bundle\PayPalBundle\Form\Type;

use Oro\Bundle\EntityExtendBundle\Form\Type\EnumSelectType;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Exception\AccessException;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class PayPalSettingsType extends AbstractType
{
    const BLOCK_PREFIX = 'oro_pay_pal_settings';

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('creditCardLabels', LocalizedFallbackValueCollectionType::class, [
                'label'    => 'oro.pay_pal.settings.?.label',
                'required' => true,
                'options'  => ['constraints' => [new NotBlank()]],
            ])
            ->add('creditCardShortLabels', LocalizedFallbackValueCollectionType::class, [
                'label'    => 'oro.pay_pal.settings.?.label',
                'required' => true,
                'options'  => ['constraints' => [new NotBlank()]],
            ])
            ->add('expressCheckoutLabels', LocalizedFallbackValueCollectionType::class, [
                'label'    => 'oro.pay_pal.settings.?.label',
                'required' => true,
                'options'  => ['constraints' => [new NotBlank()]],
            ])
            ->add('expressCheckoutShortLabels', LocalizedFallbackValueCollectionType::class, [
                'label'    => 'oro.pay_pal.settings.?.label',
                'required' => true,
                'options'  => ['constraints' => [new NotBlank()]],
            ])
            ->add('expressCheckoutName', TextType::class, [
                'label'    => 'oro.pay_pal.settings.?.label',
                'required' => true,
            ])
            ->add('creditCardPaymentAction', EnumSelectType::class, [
                'label'    => 'oro.pay_pal.settings.?.label',
                'required' => true,
                'enum_code' => '?',
            ])
            ->add('expressCheckoutPaymentAction', EnumSelectType::class, [
                'label'    => 'oro.pay_pal.settings.?.label',
                'required' => true,
                'enum_code' => '?',
            ])
            ->add('allowedCreditCardTypes', '? multiple enum', [
                'label'    => 'oro.pay_pal.settings.?.label',
                'required' => true,
            ])
            ->add('partner', TextType::class, [
                'label'    => 'oro.pay_pal.settings.?.label',
                'required' => true,
            ])
            ->add('vendor', TextType::class, [
                'label'    => 'oro.pay_pal.settings.?.label',
                'required' => true,
            ])
            ->add('user', TextType::class, [
                'label'    => 'oro.pay_pal.settings.?.label',
                'required' => true,
            ])
            ->add('password', PasswordType::class, [
                'label'    => 'oro.pay_pal.settings.?.label',
                'required' => true,
            ])
            ->add('testMode', CheckboxType::class, [
                'label'    => 'oro.pay_pal.settings.?.label',
                'required' => true,
            ])

        ;
    }

    /**
     * @param OptionsResolver $resolver
     *
     * @throws AccessException
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => '',
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::BLOCK_PREFIX;
    }
}
