<?php

namespace Oro\Bundle\PayPalBundle\Form\Type;

use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\PayPalBundle\Entity\PayPalSettings;
use Oro\Bundle\PayPalBundle\Settings\DataProvider\CardTypesDataProviderInterface;
use Oro\Bundle\PayPalBundle\Settings\DataProvider\PaymentActionsDataProviderInterface;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Exception\AccessException;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Exception\InvalidOptionsException;
use Symfony\Component\Validator\Exception\MissingOptionsException;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 */
class PayPalSettingsType extends AbstractType
{
    const BLOCK_PREFIX = 'oro_paypal_settings';

    /**
     * @var TranslatorInterface
     */
    protected $translator;

    /**
     * @var SymmetricCrypterInterface
     */
    protected $encoder;

    /**
     * @var CardTypesDataProviderInterface
     */
    private $cardTypesDataProvider;

    /**
     * @var PaymentActionsDataProviderInterface
     */
    private $paymentActionsDataProvider;

    /**
     * @param TranslatorInterface                 $translator
     * @param SymmetricCrypterInterface           $encoder
     * @param CardTypesDataProviderInterface      $cardTypesDataProvider
     * @param PaymentActionsDataProviderInterface $paymentActionsDataProvider
     */
    public function __construct(
        TranslatorInterface $translator,
        SymmetricCrypterInterface $encoder,
        CardTypesDataProviderInterface $cardTypesDataProvider,
        PaymentActionsDataProviderInterface $paymentActionsDataProvider
    ) {
        $this->translator = $translator;
        $this->encoder = $encoder;
        $this->cardTypesDataProvider = $cardTypesDataProvider;
        $this->paymentActionsDataProvider = $paymentActionsDataProvider;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param array                $options
     *
     * @throws ConstraintDefinitionException
     * @throws InvalidOptionsException
     * @throws MissingOptionsException
     * @throws \InvalidArgumentException
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('creditCardLabels', LocalizedFallbackValueCollectionType::NAME, [
                'label' => 'oro.paypal.settings.credit_card_labels.label',
                'required' => true,
                'options' => ['constraints' => [new NotBlank()]],
            ])
            ->add('creditCardShortLabels', LocalizedFallbackValueCollectionType::NAME, [
                'label' => 'oro.paypal.settings.credit_card_short_labels.label',
                'required' => true,
                'options' => ['constraints' => [new NotBlank()]],
            ])
            ->add('expressCheckoutLabels', LocalizedFallbackValueCollectionType::NAME, [
                'label' => 'oro.paypal.settings.express_checkout_labels.label',
                'required' => true,
                'options' => ['constraints' => [new NotBlank()]],
            ])
            ->add('expressCheckoutShortLabels', LocalizedFallbackValueCollectionType::NAME, [
                'label' => 'oro.paypal.settings.express_checkout_short_labels.label',
                'required' => true,
                'options' => ['constraints' => [new NotBlank()]],
            ])
            ->add('expressCheckoutName', TextType::class, [
                'label' => 'oro.paypal.settings.express_checkout_name.label',
                'required' => true,
            ])
            ->add('creditCardPaymentAction', ChoiceType::class, [
                'choices' => $this->paymentActionsDataProvider->getPaymentActions(),
                'choices_as_values' => true,
                'choice_label' => function ($action) {
                    return $this->translator->trans(
                        sprintf('oro.paypal.settings.payment_action.%s', $action)
                    );
                },
                'label' => 'oro.paypal.settings.credit_card_payment_action.label',
                'required' => true,
            ])
            ->add('expressCheckoutPaymentAction', ChoiceType::class, [
                'choices' => $this->paymentActionsDataProvider->getPaymentActions(),
                'choices_as_values' => true,
                'choice_label' => function ($action) {
                    return $this->translator->trans(
                        sprintf('oro.paypal.settings.payment_action.%s', $action)
                    );
                },
                'label' => 'oro.paypal.settings.express_checkout_payment_action.label',
                'required' => true,
            ])
            ->add('allowedCreditCardTypes', ChoiceType::class, [
                'choices' => $this->cardTypesDataProvider->getCardTypes(),
                'choices_as_values' => true,
                'choice_label' => function ($cardType) {
                    return $this->translator->trans(
                        sprintf('oro.paypal.settings.allowed_cc_types.%s', $cardType)
                    );
                },
                'label' => 'oro.paypal.settings.allowed_cc_types.label',
                'required' => true,
                'multiple' => true,
            ])
            ->add('partner', TextType::class, [
                'label' => 'oro.paypal.settings.partner.label',
                'required' => true,
            ])
            ->add('vendor', TextType::class, [
                'label' => 'oro.paypal.settings.vendor.label',
                'required' => true,
            ])
            ->add('user', TextType::class, [
                'label' => 'oro.paypal.settings.user.label',
                'required' => true,
            ])
            ->add('password', PasswordType::class, [
                'label' => 'oro.paypal.settings.password.label',
                'required' => true,
            ])
            ->add('testMode', CheckboxType::class, [
                'label' => 'oro.paypal.settings.test_mode.label',
                'required' => false,
            ])
            ->add('debugMode', CheckboxType::class, [
                'label' => 'oro.paypal.settings.debug_mode.label',
                'required' => false,
            ])
            ->add('requireCVVEntry', CheckboxType::class, [
                'label' => 'oro.paypal.settings.require_cvv.label',
                'required' => false,
            ])
            ->add('zeroAmountAuthorization', CheckboxType::class, [
                'label' => 'oro.paypal.settings.zero_amount_authorization.label',
                'required' => false,
            ])
            ->add('authorizationForRequiredAmount', CheckboxType::class, [
                'label' => 'oro.paypal.settings.authorization_for_required_amount.label',
                'required' => false,
            ])
            ->add('useProxy', CheckboxType::class, [
                'label' => 'oro.paypal.settings.use_proxy.label',
                'required' => false,
            ])
            ->add('proxyHost', TextType::class, [
                'label' => 'oro.paypal.settings.proxy_host.label',
                'required' => false,
            ])
            ->add('proxyPort', TextType::class, [
                'label' => 'oro.paypal.settings.proxy_port.label',
                'required' => false,
            ])
            ->add('enableSSLVerification', CheckboxType::class, [
                'label' => 'oro.paypal.settings.enable_ssl_verification.label',
                'required' => false,
            ]);
        $this->transformWithEncodedValue($builder, 'vendor');
        $this->transformWithEncodedValue($builder, 'partner');
        $this->transformWithEncodedValue($builder, 'user');
        $this->transformWithEncodedValue($builder, 'password', false);
        $this->transformWithEncodedValue($builder, 'proxyHost');
        $this->transformWithEncodedValue($builder, 'proxyPort');
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
     * {@inheritDoc}
     */
    public function getBlockPrefix()
    {
        return self::BLOCK_PREFIX;
    }

    /**
     * @param FormBuilderInterface $builder
     * @param string               $field
     * @param bool                 $decrypt
     *
     * @throws \InvalidArgumentException
     */
    protected function transformWithEncodedValue(FormBuilderInterface $builder, $field, $decrypt = true)
    {
        $builder->get($field)->addModelTransformer(new CallbackTransformer(
            function ($value) use ($decrypt) {
                if ($decrypt === true) {
                    return $this->encoder->decryptData($value);
                }

                return $value;
            },
            function ($value) {
                return $this->encoder->encryptData($value);
            }
        ));
    }
}
