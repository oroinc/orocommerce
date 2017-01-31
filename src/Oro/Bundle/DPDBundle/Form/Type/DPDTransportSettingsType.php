<?php

namespace Oro\Bundle\DPDBundle\Form\Type;

use Oro\Bundle\CurrencyBundle\Rounding\RoundingServiceInterface;
use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\IntegrationBundle\Provider\TransportInterface;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Oro\Bundle\ShippingBundle\Form\Type\WeightUnitSelectType;
use Oro\Bundle\DPDBundle\Entity\DPDTransport;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\Exception\AccessException;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Exception\ConstraintDefinitionException;
use Symfony\Component\Validator\Exception\InvalidOptionsException;
use Symfony\Component\Validator\Exception\MissingOptionsException;

class DPDTransportSettingsType extends AbstractType
{
    const BLOCK_PREFIX = 'oro_dpd_transport_settings';

    /**
     * @var string
     */
    protected $dataClass;

    /**
     * @var TransportInterface
     */
    protected $transport;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /** @var SymmetricCrypterInterface */
    protected $symmetricCrypter;

    /**
     * @var RoundingServiceInterface
     */
    protected $roundingService;

    /**
     * DPDTransportSettingsType constructor.
     *
     * @param TransportInterface        $transport
     * @param DoctrineHelper            $doctrineHelper
     * @param SymmetricCrypterInterface $symmetricCrypter
     * @param RoundingServiceInterface  $roundingService
     */
    public function __construct(
        TransportInterface $transport,
        DoctrineHelper $doctrineHelper,
        SymmetricCrypterInterface $symmetricCrypter,
        RoundingServiceInterface $roundingService
    ) {
        $this->transport = $transport;
        $this->doctrineHelper = $doctrineHelper;
        $this->symmetricCrypter = $symmetricCrypter;
        $this->roundingService = $roundingService;
    }

    /**
     * {@inheritdoc}
     *
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     *
     * @throws ConstraintDefinitionException
     * @throws InvalidOptionsException
     * @throws MissingOptionsException
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'labels',
            LocalizedFallbackValueCollectionType::class,
            [
                'label' => 'oro.dpd.transport.labels.label',
                'required' => true,
                'options' => ['constraints' => [new NotBlank()]],
            ]
        );
        $builder->add(
            'liveMode',
            CheckboxType::class,
            [
                'label' => 'oro.dpd.transport.live_mode.label',
                'required' => false,
            ]
        );
        $builder->add(
            'cloudUserId',
            TextType::class,
            [
                'label' => 'oro.dpd.transport.cloud_user_id.label',
                'required' => true,
            ]
        );
        $builder->add(
            'cloudUserToken',
            PasswordType::class,
            [
                'label' => 'oro.dpd.transport.cloud_user_token.label',
                'required' => true,
            ]
        );
        $builder->get('cloudUserToken')
            ->addModelTransformer(new CallbackTransformer(
                function ($password) {
                    return $password;
                },
                function ($password) {
                    return $this->symmetricCrypter->encryptData($password);
                }
            ));
        $builder->add(
            'applicableShippingServices',
            'entity',
            [
                'label' => 'oro.dpd.transport.shipping_service.plural_label',
                'required' => true,
                'multiple' => true,
                'class' => 'Oro\Bundle\DPDBundle\Entity\ShippingService',
            ]
        );
        $builder->add(
            'unitOfWeight',
            WeightUnitSelectType::NAME,
            [
                'placeholder' => 'oro.shipping.form.placeholder.weight_unit.label',
                'required' => true,
            ]
        );
        $builder->add(
            'ratePolicy',
            ChoiceType::class,
            [
                'required' => true,
                'choices' => [
                    DPDTransport::FLAT_RATE_POLICY => 'oro.dpd.transport.rate_policy.flat_rate.label',
                    DPDTransport::TABLE_RATE_POLICY => 'oro.dpd.transport.rate_policy.table_rate.label',
                ],
                'label' => 'oro.dpd.transport.rate_policy.label',
            ]
        );
        $builder->add(
            'flatRatePriceValue',
            NumberType::class,
            [
                'label' => 'oro.dpd.transport.flat_rate_price_value.label',
                'required' => false,
                'scale' => $this->roundingService->getPrecision(),
                'rounding_mode' => $this->roundingService->getRoundType(),
                'attr' => [
                    'data-scale' => $this->roundingService->getPrecision(),
                    'class' => 'method-options-surcharge',
                ],
            ]
        );
        $builder->add(
            'ratesCsv',
            RatesCsvType::class,
            [
                'label' => 'oro.dpd.transport.rates_csv.label',
                'required' => false,
            ]
        );
        $builder->add(
            'labelSize',
            ChoiceType::class,
            [
                'required' => true,
                'choices' => [
                    DPDTransport::PDF_A4_LABEL_SIZE => 'oro.dpd.transport.label_size.pdf_a4.label',
                    DPDTransport::PDF_A6_LABEL_SIZE => 'oro.dpd.transport.label_size.pdf_a6.label',
                ],
                'label' => 'oro.dpd.transport.label_size.label',
            ]
        );
        $builder->add(
            'labelStartPosition',
            ChoiceType::class,
            [
                'required' => true,
                'choices' => [
                    DPDTransport::UPPERLEFT_LABEL_START_POSITION =>
                        'oro.dpd.transport.label_start_position.upperleft.label',
                    DPDTransport::UPPERRIGHT_LABEL_START_POSITION =>
                        'oro.dpd.transport.label_start_position.upperright.label',
                    DPDTransport::LOWERLEFT_LABEL_START_POSITION =>
                        'oro.dpd.transport.label_start_position.lowerleft.label',
                    DPDTransport::LOWERRIGHT_LABEL_START_POSITION =>
                        'oro.dpd.transport.label_start_position.lowerright.label',
                ],
                'label' => 'oro.dpd.transport.label_start_position.label',
            ]
        );
    }

    /**
     * {@inheritdoc}
     *
     * @throws AccessException
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => $this->dataClass ?: $this->transport->getSettingsEntityFQCN(),
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
