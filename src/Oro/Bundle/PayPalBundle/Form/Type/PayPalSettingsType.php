<?php

namespace Oro\Bundle\PayPalBundle\Form\Type;

use Oro\Bundle\EntityExtendBundle\Form\Type\EnumSelectType;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\PayPalBundle\Entity\PayPalSettings;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Exception\AccessException;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Exception\InvalidOptionsException;
use Symfony\Component\Validator\Exception\MissingOptionsException;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 */
class PayPalSettingsType extends AbstractType
{
    const BLOCK_PREFIX = 'oro_pay_pal_settings';

    /**
     * @param FormBuilderInterface $builder
     * @param array $options
     *
     * @throws ConstraintDefinitionException
     * @throws InvalidOptionsException
     * @throws MissingOptionsException
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('creditCardLabels', LocalizedFallbackValueCollectionType::class, [
                'label'    => 'oro.pay_pal.settings.credit_card_labels.label',
                'required' => true,
                'options'  => ['constraints' => [new NotBlank()]],
            ])
            ->add('creditCardShortLabels', LocalizedFallbackValueCollectionType::class, [
                'label'    => 'oro.pay_pal.settings.credit_card_short_labels.label',
                'required' => true,
                'options'  => ['constraints' => [new NotBlank()]],
            ])
            ->add('expressCheckoutLabels', LocalizedFallbackValueCollectionType::class, [
                'label'    => 'oro.pay_pal.settings.credit_card_labels.label',
                'required' => true,
                'options'  => ['constraints' => [new NotBlank()]],
            ])
            ->add('expressCheckoutShortLabels', LocalizedFallbackValueCollectionType::class, [
                'label'    => 'oro.pay_pal.settings.express_checkout_short_labels.label',
                'required' => true,
                'options'  => ['constraints' => [new NotBlank()]],
            ])
            ->add('expressCheckoutName', TextType::class, [
                'label'    => 'oro.pay_pal.settings.express_checkout_name.label',
                'required' => true,
            ])
            ->add('creditCardPaymentAction', EnumSelectType::class, [
                'label'    => 'oro.pay_pal.settings.credit_card_payment_action.label',
                'required' => true,
                'enum_code' => 'pp_credit_card_payment_action',
            ])
            ->add('expressCheckoutPaymentAction', EnumSelectType::class, [
                'label'    => 'oro.pay_pal.settings.express_checkout_payment_action.label',
                'required' => true,
                'enum_code' => 'pp_express_checkout_payment_action',
            ])
            ->add('allowedCreditCardTypes', EnumSelectType::class, [
                'label'    => 'oro.pay_pal.settings.allowed_credit_card_types.label',
                'required' => true,
                'enum_code' => 'pp_credit_card_types',
                'multiple' => true,
            ])
            ->add('partner', TextType::class, [
                'label'    => 'oro.pay_pal.settings.partner.label',
                'required' => true,
            ])
            ->add('vendor', TextType::class, [
                'label'    => 'oro.pay_pal.settings.vendor.label',
                'required' => true,
            ])
            ->add('user', TextType::class, [
                'label'    => 'oro.pay_pal.settings.user.label',
                'required' => true,
            ])
            ->add('password', PasswordType::class, [
                'label'    => 'oro.pay_pal.settings.password.label',
                'required' => true,
            ])
            ->add('testMode', CheckboxType::class, [
                'label'    => 'oro.pay_pal.settings.test_mode.label',
                'required' => true,
            ])
            ->add('debugMode', CheckboxType::class, [
                'label'    => 'oro.pay_pal.settings.debug_mode.label',
                'required' => false,
            ])
            ->add('requireCVVEntry', CheckboxType::class, [
                'label'    => 'oro.pay_pal.settings.require_CVV_entry.label',
                'required' => false,
            ])
            ->add('zeroAmountAuthorization', CheckboxType::class, [
                'label'    => 'oro.pay_pal.settings.zero_amount_authorization.label',
                'required' => false,
            ])
            ->add('authorizationForRequiredAmount', CheckboxType::class, [
                'label'    => 'oro.pay_pal.settings.authorization_for_required_amount.label',
                'required' => false,
            ])
            ->add('useProxy', CheckboxType::class, [
                'label'    => 'oro.pay_pal.settings.use_proxy.label',
                'required' => false,
            ])
            ->add('proxyHost', TextType::class, [
                'label'    => 'oro.pay_pal.settings.proxy_host.label',
                'required' => true,
            ])
            ->add('proxyPort', TextType::class, [
                'label'    => 'oro.pay_pal.settings.proxy_port.label',
                'required' => true,
            ])
            ->add('enableSSLVerification', CheckboxType::class, [
                'label'    => 'oro.pay_pal.settings.enable_SSL_verification.label',
                'required' => false,
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
            'data_class' => PayPalSettings::class,
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
