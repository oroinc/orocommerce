<?php

namespace Oro\Bundle\AuthorizeNetBundle\Form\Type;

use Oro\Bundle\AuthorizeNetBundle\Entity\AuthorizeNetSettings;
use Oro\Bundle\AuthorizeNetBundle\Settings\DataProvider\CardTypesDataProviderInterface;
use Oro\Bundle\AuthorizeNetBundle\Settings\DataProvider\PaymentActionsDataProviderInterface;
use Oro\Bundle\FormBundle\Form\Type\OroEncodedPlaceholderPasswordType;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\SecurityBundle\Form\DataTransformer\Factory\CryptedDataTransformerFactoryInterface;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Translation\TranslatorInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

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
     * @var CardTypesDataProviderInterface
     */
    protected $cardTypesDataProvider;

    /**
     * @var PaymentActionsDataProviderInterface
     */
    protected $paymentActionsDataProvider;

    /**
     * @var CryptedDataTransformerFactoryInterface
     */
    protected $cryptedDataTransformerFactory;

    /**
     * @param TranslatorInterface                    $translator
     * @param CryptedDataTransformerFactoryInterface $cryptedDataTransformerFactory
     * @param CardTypesDataProviderInterface         $cardTypesDataProvider
     * @param PaymentActionsDataProviderInterface    $paymentActionsDataProvider
     */
    public function __construct(
        TranslatorInterface $translator,
        CryptedDataTransformerFactoryInterface $cryptedDataTransformerFactory,
        CardTypesDataProviderInterface $cardTypesDataProvider,
        PaymentActionsDataProviderInterface $paymentActionsDataProvider
    ) {
        $this->translator = $translator;
        $this->cryptedDataTransformerFactory = $cryptedDataTransformerFactory;
        $this->cardTypesDataProvider = $cardTypesDataProvider;
        $this->paymentActionsDataProvider = $paymentActionsDataProvider;
    }

    /**
     * {@inheritdoc}
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
            ->add(
                'apiLoginId',
                TextType::class,
                [
                    'label' => 'oro.authorize_net.settings.api_login.label',
                    'required' => true,
                    'attr' => ['autocomplete' => 'off'],
                ]
            )
            ->add('transactionKey', OroEncodedPlaceholderPasswordType::class, [
                'label' => 'oro.authorize_net.settings.transaction_key.label',
                'required' => true,
                'attr' => ['autocomplete' => 'new-password'],
            ])
            ->add('clientKey', TextType::class, [
                'label' => 'oro.authorize_net.settings.client_key.label',
                'required' => true,
                'attr' => ['autocomplete' => 'off'],
            ])
            ->add(
                'authNetRequireCVVEntry',
                CheckboxType::class,
                [
                    'label' => 'oro.authorize_net.settings.require_cvv.label',
                    'required' => false,
                ]
            )
            ->add(
                'authNetTestMode',
                CheckboxType::class,
                [
                    'label' => 'oro.authorize_net.settings.test_mode.label',
                    'required' => false,
                ]
            );

        $this->transformWithEncodedValue($builder, 'apiLoginId');
        $this->transformWithEncodedValue($builder, 'clientKey');
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => AuthorizeNetSettings::class,
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
     * @param FormBuilderInterface $builder
     * @param string $field
     */
    protected function transformWithEncodedValue(FormBuilderInterface $builder, $field)
    {
        $builder->get($field)->addModelTransformer($this->cryptedDataTransformerFactory->create());
    }
}
