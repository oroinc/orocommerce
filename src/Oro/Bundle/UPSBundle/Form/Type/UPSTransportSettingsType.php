<?php

namespace Oro\Bundle\UPSBundle\Form\Type;

use Oro\Bundle\AddressBundle\Form\Type\CountryType;
use Oro\Bundle\EntityBundle\Exception\NotManageableEntityException;
use Oro\Bundle\FormBundle\Form\Type\OroEncodedPlaceholderPasswordType;
use Oro\Bundle\IntegrationBundle\Provider\TransportInterface;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\ShippingBundle\Provider\ShippingOriginProvider;
use Oro\Bundle\UPSBundle\Entity\Repository\ShippingServiceRepository;
use Oro\Bundle\UPSBundle\Entity\ShippingService;
use Oro\Bundle\UPSBundle\Entity\UPSTransport;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
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

/**
 * UPS integration settings form type.
 */
class UPSTransportSettingsType extends AbstractType
{
    const BLOCK_PREFIX = 'oro_ups_transport_settings';

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

    public function __construct(TransportInterface $transport, ShippingOriginProvider $shippingOriginProvider)
    {
        $this->transport = $transport;
        $this->shippingOriginProvider = $shippingOriginProvider;
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
                'label' => 'oro.ups.transport.labels.label',
                'required' => true,
                'entry_options' => ['constraints' => [new NotBlank()]],
            ]
        );
        $builder->add(
            'upsTestMode',
            CheckboxType::class,
            [
                'label' => 'oro.ups.transport.test_mode.label',
                'required' => false,
            ]
        );
        $builder->add(
            'upsApiUser',
            TextType::class,
            [
                'label' => 'oro.ups.transport.api_user.label',
                'required' => true,
                'attr' => ['autocomplete' => 'off'],
            ]
        );
        $builder->add(
            'upsApiPassword',
            OroEncodedPlaceholderPasswordType::class,
            [
                'label' => 'oro.ups.transport.api_password.label',
                'required' => true
            ]
        );
        $builder->add(
            'upsApiKey',
            TextType::class,
            [
                'label' => 'oro.ups.transport.api_key.label',
                'required' => true,
                'attr' => ['autocomplete' => 'off'],
            ]
        );
        $builder->add(
            'upsShippingAccountName',
            TextType::class,
            [
                'label' => 'oro.ups.transport.shipping_account_name.label',
                'required' => true
            ]
        );
        $builder->add(
            'upsShippingAccountNumber',
            TextType::class,
            [
                'label' => 'oro.ups.transport.shipping_account_number.label',
                'required' => true
            ]
        );
        $builder->add(
            'upsPickupType',
            ChoiceType::class,
            [
                'label' => 'oro.ups.transport.pickup_type.label',
                'required' => true,
                'choices' => [
                    'oro.ups.transport.pickup_type.regular_daily.label' => UPSTransport::PICKUP_TYPE_REGULAR_DAILY,
                    'oro.ups.transport.pickup_type.customer_counter.label' =>
                        UPSTransport::PICKUP_TYPE_CUSTOMER_COUNTER,
                    'oro.ups.transport.pickup_type.one_time.label' => UPSTransport::PICKUP_TYPE_ONE_TIME,
                    'oro.ups.transport.pickup_type.on_call_air.label' => UPSTransport::PICKUP_TYPE_ON_CALL_AIR,
                    'oro.ups.transport.pickup_type.letter_center.label' => UPSTransport::PICKUP_TYPE_LETTER_CENTER,
                ]
            ]
        );
        $builder->add(
            'upsUnitOfWeight',
            ChoiceType::class,
            [
                'label' => 'oro.ups.transport.unit_of_weight.label',
                'required' => true,
                'choices' => [
                    'oro.ups.transport.unit_of_weight.lbs.label' => UPSTransport::UNIT_OF_WEIGHT_LBS,
                    'oro.ups.transport.unit_of_weight.kgs.label' => UPSTransport::UNIT_OF_WEIGHT_KGS,
                ]
            ]
        );
        $builder->add(
            'upsCountry',
            CountryType::class,
            [
                'label' => 'oro.ups.transport.country.label',
                'required' => true,
            ]
        );
        $builder->add(
            'applicableShippingServices',
            EntityType::class,
            $this->getApplicableShippingServicesOptions()
        );
        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'onPreSetData']);
    }

    /**
     * @throws NotManageableEntityException
     */
    public function onPreSetData(FormEvent $event)
    {
        $this->setDefaultCountry($event);

        $this->setApplicableShippingServicesChoicesByCountry($event);
    }

    protected function setDefaultCountry(FormEvent $event)
    {
        /** @var UPSTransport $transport */
        $transport = $event->getData();

        if (!$transport) {
            return;
        }

        if ($transport && null === $transport->getUpsCountry()) {
            $country = $this
                ->shippingOriginProvider
                ->getSystemShippingOrigin()
                ->getCountry();

            if (null !== $country) {
                $transport->setUpsCountry($country);
            }
        }
    }

    protected function setApplicableShippingServicesChoicesByCountry(FormEvent $event)
    {
        /** @var UPSTransport $transport */
        $transport = $event->getData();
        $form = $event->getForm();

        if (!$transport) {
            return;
        }

        $country = $transport->getUpsCountry();

        $additionalOptions = [
            'choices' => [],
        ];
        if ($country) {
            $additionalOptions = [
                'query_builder' => function (ShippingServiceRepository $repository) use ($country) {
                    return $repository->createQueryBuilder('service')
                        ->where('service.country = :country')
                        ->setParameter('country', $country);
                },
            ];
        }

        $form->add('applicableShippingServices', EntityType::class, array_merge(
            $this->getApplicableShippingServicesOptions(),
            $additionalOptions
        ));
    }

    /**
     * @return array
     */
    protected function getApplicableShippingServicesOptions()
    {
        return [
            'label' => 'oro.ups.transport.shipping_service.plural_label',
            'required' => true,
            'multiple' => true,
            'class' => ShippingService::class,
        ];
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
