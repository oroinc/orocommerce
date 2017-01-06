<?php

namespace Oro\Bundle\DPDBundle\Form\Type;

use Oro\Bundle\EntityBundle\ORM\DoctrineHelper;
use Oro\Bundle\IntegrationBundle\Provider\TransportInterface;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\SecurityBundle\Encoder\SymmetricCrypterInterface;
use Oro\Bundle\ShippingBundle\Provider\ShippingOriginProvider;
use Oro\Bundle\DPDBundle\Entity\DPDTransport;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
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
     * @var ShippingOriginProvider
     */
    protected $shippingOriginProvider;

    /**
     * @var DoctrineHelper
     */
    protected $doctrineHelper;

    /** @var SymmetricCrypterInterface */
    protected $symmetricCrypter;

    /**
     * DPDTransportSettingsType constructor.
     *
     * @param TransportInterface        $transport
     * @param ShippingOriginProvider    $shippingOriginProvider
     * @param DoctrineHelper            $doctrineHelper
     * @param SymmetricCrypterInterface $symmetricCrypter
     */
    public function __construct(
        TransportInterface $transport,
        ShippingOriginProvider $shippingOriginProvider,
        DoctrineHelper $doctrineHelper,
        SymmetricCrypterInterface $symmetricCrypter
    ) {
        $this->transport = $transport;
        $this->shippingOriginProvider = $shippingOriginProvider;
        $this->doctrineHelper = $doctrineHelper;
        $this->symmetricCrypter = $symmetricCrypter;
    }

    /**
     * {@inheritdoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
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
                'required' => false
            ]
        );
        $builder->add(
            'cloudUserId',
            TextType::class,
            [
                'label' => 'oro.dpd.transport.cloud_user_id.label',
                'required' => true
            ]
        );
        $builder->add(
            'cloudUserToken',
            PasswordType::class,
            [
                'label' => 'oro.dpd.transport.cloud_user_token.label',
                'required' => true
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
        $builder->add(DPDTransport::LABEL_SIZE_OPTION, ChoiceType::class, [
                'required' => true,
                'choices' => [
                    DPDTransport::PDF_A4_LABEL_SIZE
                    => 'oro.dpd.transport.label_size.pdf_a4.label',
                    DPDTransport::PDF_A6_LABEL_SIZE
                    => 'oro.dpd.transport.label_size.pdf_a6.label',
                ],
                'label' => 'oro.dpd.transport.label_size.label',
        ]);
        $builder->add(DPDTransport::LABEL_START_POSTITION_OPTION, ChoiceType::class, [
            'required' => true,
            'choices' => [
                DPDTransport::UPPERLEFT_LABEL_START_POSITION
                => 'oro.dpd.transport.label_start_position.upperleft.label',
                DPDTransport::UPPERRIGHT_LABEL_START_POSITION
                => 'oro.dpd.transport.label_start_position.upperright.label',
                DPDTransport::LOWERLEFT_LABEL_START_POSITION
                => 'oro.dpd.transport.label_start_position.lowerleft.label',
                DPDTransport::LOWERRIGHT_LABEL_START_POSITION
                => 'oro.dpd.transport.label_start_position.lowerright.label',
            ],
            'label' => 'oro.dpd.transport.label_start_position.label',
        ]);
    }

    /**
     * @param ShippingOriginProvider $shippingOriginProvider
     */
    public function setShippingOriginProvider($shippingOriginProvider)
    {
        $this->shippingOriginProvider = $shippingOriginProvider;
    }

    /**
     * {@inheritdoc}
     * @throws AccessException
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => $this->dataClass ?: $this->transport->getSettingsEntityFQCN()
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
