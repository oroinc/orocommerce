<?php

namespace Oro\Bundle\AuthorizeNetBundle\Form\Type;

use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\AuthorizeNetBundle\Entity\AuthorizeNetSettings;
use Oro\Bundle\PayPalBundle\Settings\DataProvider\CardTypesDataProviderInterface;
use Oro\Bundle\PayPalBundle\Settings\DataProvider\PaymentActionsDataProviderInterface;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Exception\AccessException;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Exception\InvalidOptionsException;
use Symfony\Component\Validator\Exception\MissingOptionsException;

/**
 * @SuppressWarnings(PHPMD.CouplingBetweenObjects)
 * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
 */
class AuthorizeNetSettingsType extends AbstractType
{
    const BLOCK_PREFIX = 'oro_authorize_net_settings';

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
     * @throws InvalidOptionsException
     * @throws MissingOptionsException
     * @throws \InvalidArgumentException
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('creditCardLabels', LocalizedFallbackValueCollectionType::NAME, [
                'label' => 'oro.authorize_net.settings.credit_card_labels.label',
                'required' => true,
                'options' => ['constraints' => [new NotBlank()]],
            ])
            ->add('creditCardShortLabels', LocalizedFallbackValueCollectionType::NAME, [
                'label' => 'oro.authorize_net.settings.credit_card_short_labels.label',
                'required' => true,
                'options' => ['constraints' => [new NotBlank()]],
            ])
            ->add('creditCardPaymentAction', ChoiceType::class, [
                'choices' => $this->paymentActionsDataProvider->getPaymentActions(),
                'choices_as_values' => true,
                'choice_label' => function ($action) {
                    return $this->translator->trans(
                        sprintf('oro.authorize_net.settings.payment_action.%s', $action)
                    );
                },
                'label' => 'oro.authorize_net.settings.credit_card_payment_action.label',
                'required' => true,
            ])
            ->add('allowedCreditCardTypes', ChoiceType::class, [
                'choices' => $this->cardTypesDataProvider->getCardTypes(),
                'choices_as_values' => true,
                'choice_label' => function ($cardType) {
                    return $this->translator->trans(
                        sprintf('oro.authorize_net.settings.allowed_cc_types.%s', $cardType)
                    );
                },
                'label' => 'oro.authorize_net.settings.allowed_cc_types.label',
                'required' => true,
                'multiple' => true,
            ])
            ->add('apiLogin', TextType::class, [
                'label' => 'oro.authorize_net.settings.api_login.label',
                'required' => true,
            ])
            ->add('transactionKey', TextType::class, [
                'label' => 'oro.authorize_net.settings.transaction_key.label',
                'required' => true,
            ])
            ->add('clientKey', TextType::class, [
                'label' => 'oro.authorize_net.settings.client_key.label',
                'required' => true,
            ])
            ->add('testMode', CheckboxType::class, [
                'label' => 'oro.authorize_net.settings.test_mode.label',
                'required' => false,
            ]);
        $this->transformWithEncodedValue($builder, 'apiLogin');
        $this->transformWithEncodedValue($builder, 'transactionKey');
        $this->transformWithEncodedValue($builder, 'clientKey');
    }

    /**
     * @param OptionsResolver $resolver
     *
     * @throws AccessException
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => AuthorizeNetSettings::class,
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
