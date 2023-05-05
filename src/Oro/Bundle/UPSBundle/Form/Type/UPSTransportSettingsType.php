<?php

namespace Oro\Bundle\UPSBundle\Form\Type;

use Oro\Bundle\AddressBundle\Form\Type\CountryType;
use Oro\Bundle\FormBundle\Form\Type\OroEncodedPlaceholderPasswordType;
use Oro\Bundle\IntegrationBundle\Provider\TransportInterface;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\ShippingBundle\Provider\SystemShippingOriginProvider;
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
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * The form type for UPS integration settings.
 */
class UPSTransportSettingsType extends AbstractType
{
    private TransportInterface $transport;
    private SystemShippingOriginProvider $systemShippingOriginProvider;

    public function __construct(
        TransportInterface $transport,
        SystemShippingOriginProvider $systemShippingOriginProvider
    ) {
        $this->transport = $transport;
        $this->systemShippingOriginProvider = $systemShippingOriginProvider;
    }

    /**
     * {@inheritDoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function buildForm(FormBuilderInterface $builder, array $options): void
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
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            $this->setDefaultCountry($event);
            $this->setApplicableShippingServicesChoicesByCountry($event);
        });
    }

    /**
     * {@inheritDoc}
     */
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => $this->transport->getSettingsEntityFQCN()
        ]);
    }

    /**
     * {@inheritDoc}
     */
    public function getBlockPrefix(): string
    {
        return 'oro_ups_transport_settings';
    }

    private function setDefaultCountry(FormEvent $event): void
    {
        /** @var UPSTransport|null $transport */
        $transport = $event->getData();
        if (!$transport) {
            return;
        }

        if (null === $transport->getUpsCountry()) {
            $country = $this->systemShippingOriginProvider->getSystemShippingOrigin()->getCountry();
            if (null !== $country) {
                $transport->setUpsCountry($country);
            }
        }
    }

    private function setApplicableShippingServicesChoicesByCountry(FormEvent $event): void
    {
        /** @var UPSTransport|null $transport */
        $transport = $event->getData();
        if (!$transport) {
            return;
        }

        $additionalOptions = [
            'choices' => [],
        ];
        $country = $transport->getUpsCountry();
        if ($country) {
            $additionalOptions = [
                'query_builder' => function (ShippingServiceRepository $repository) use ($country) {
                    return $repository->createQueryBuilder('service')
                        ->where('service.country = :country')
                        ->setParameter('country', $country);
                },
            ];
        }

        $event->getForm()->add('applicableShippingServices', EntityType::class, array_merge(
            $this->getApplicableShippingServicesOptions(),
            $additionalOptions
        ));
    }

    private function getApplicableShippingServicesOptions(): array
    {
        return [
            'label' => 'oro.ups.transport.shipping_service.plural_label',
            'required' => true,
            'multiple' => true,
            'class' => ShippingService::class,
        ];
    }
}
