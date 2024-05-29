<?php

namespace Oro\Bundle\FedexShippingBundle\Form\Type;

use Oro\Bundle\FedexShippingBundle\Cache\FedexResponseCacheInterface;
use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
use Oro\Bundle\FedexShippingBundle\Entity\FedexShippingService;
use Oro\Bundle\FormBundle\Form\Type\OroEncodedPlaceholderPasswordType;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Oro\Bundle\ShippingBundle\Provider\Cache\ShippingPriceCache;
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

/**
 * Settings form of the FedEx integration.
 */
class FedexIntegrationSettingsType extends AbstractType
{
    const BLOCK_PREFIX = 'oro_fedex_settings';

    private FedexResponseCacheInterface $fedexResponseCache;
    private ShippingPriceCache $shippingPriceCache;

    public function __construct(
        FedexResponseCacheInterface $fedexResponseCache,
        ShippingPriceCache $shippingPriceCache
    ) {
        $this->fedexResponseCache = $fedexResponseCache;
        $this->shippingPriceCache = $shippingPriceCache;
    }

    /**
     * {@inheritDoc}
     * @SuppressWarnings(PHPMD.ExcessiveMethodLength)
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'labels',
                LocalizedFallbackValueCollectionType::class,
                [
                    'label' => 'oro.fedex.integration.settings.labels.label',
                    'required' => true,
                    'entry_options' => ['constraints' => [new NotBlank()]],
                ]
            )
            ->add(
                'fedexTestMode',
                CheckboxType::class,
                [
                    'label' => 'oro.fedex.integration.settings.test_mode.label',
                    'required' => false,
                ]
            )
            ->add(
                'key',
                TextType::class,
                [
                    'label' => 'oro.fedex.integration.settings.key.label',
                    'required' => false,
                ]
            )
            ->add(
                'password',
                OroEncodedPlaceholderPasswordType::class,
                [
                    'label' => 'oro.fedex.integration.settings.password.label',
                    'required' => false,
                ]
            )
            ->add(
                'accountNumberSoap',
                TextType::class,
                [
                    'label' => 'oro.fedex.integration.settings.account_number_soap.label',
                    'required' => false
                ]
            )
            ->add(
                'pickupTypeSoap',
                ChoiceType::class,
                [
                    'label' => 'oro.fedex.integration.settings.pickup_type_soap.label',
                    'required' => false,
                    'choices' => $this->getChoices()
                ]
            )
            ->add(
                'meterNumber',
                TextType::class,
                [
                    'label' => 'oro.fedex.integration.settings.meter_number.label',
                    'required' => false
                ]
            )
            ->add(
                'clientId',
                TextType::class,
                [
                    'label' => 'oro.fedex.integration.settings.client_id.label',
                    'required' => true,
                    'constraints' => [new NotBlank()],
                ]
            )
            ->add(
                'clientSecret',
                OroEncodedPlaceholderPasswordType::class,
                [
                    'label' => 'oro.fedex.integration.settings.client_secret.label',
                    'required' => true,
                    'constraints' => [new NotBlank()],
                ]
            )
            ->add(
                'accountNumber',
                TextType::class,
                [
                    'label' => 'oro.fedex.integration.settings.account_number.label',
                    'required' => true
                ]
            )
            ->add(
                'pickupType',
                ChoiceType::class,
                [
                    'label' => 'oro.fedex.integration.settings.pickup_type.label',
                    'required' => true,
                    'choices' => $this->getPickupChoices(),
                    'constraints' => [new NotBlank()],
                ]
            )
            ->add(
                'unitOfWeight',
                ChoiceType::class,
                [
                    'label' => 'oro.fedex.integration.settings.unit_of_weight.label',
                    'required' => true,
                    'choices' => [
                        'oro.fedex.integration.settings.unit_of_weight.lb.label' =>
                            FedexIntegrationSettings::UNIT_OF_WEIGHT_LB,
                        'oro.fedex.integration.settings.unit_of_weight.kg.label' =>
                            FedexIntegrationSettings::UNIT_OF_WEIGHT_KG,
                    ]
                ]
            )
            ->add(
                'shippingServices',
                EntityType::class,
                [
                    'class' => FedexShippingService::class,
                    'choice_label' => 'description',
                    'label' => 'oro.fedex.integration.settings.shipping_services.label',
                    'required' => true,
                    'multiple' => true,
                ]
            )
            ->add(
                'ignorePackageDimensions',
                CheckboxType::class,
                [
                    'label' => 'oro.fedex.integration.settings.ignore_package_dimensions.label',
                    'tooltip' => 'oro.fedex.integration.settings.ignore_package_dimensions.tooltip',
                    'required' => false,
                ]
            );

        $builder->addEventListener(FormEvents::PRE_SET_DATA, [$this, 'preSetData']);

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event) {
            if ($event->getForm()->isValid()) {
                $this->fedexResponseCache->deleteAll();
                $this->shippingPriceCache->deleteAllPrices();
            }
        });
    }

    public function preSetData(FormEvent $event): void
    {
        $form = $event->getForm();
        /** @var FedexIntegrationSettings $entity */
        $entity = $event->getData();

        if (!$entity || !$entity->getId() || ($entity->getClientSecret() && $entity->getClientId())) {
            $form->remove('key');
            $form->remove('password');
            $form->remove('meterNumber');
            $form->remove('pickupTypeSoap');
            $form->remove('accountNumberSoap');
        }
    }

    /**
     * {@inheritDoc}
     *
     * @throws AccessException
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => FedexIntegrationSettings::class
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::BLOCK_PREFIX;
    }

    private function getPickupChoices(): array
    {
        return [
            'oro.fedex.integration.settings.pickup_type.contact_fedex_to_schedule.label' =>
                FedexIntegrationSettings::PICKUP_CONTACT_FEDEX_TO_SCHEDULE,
            'oro.fedex.integration.settings.pickup_type.dropoff_at_fedex_location.label' =>
                FedexIntegrationSettings::PICKUP_DROPOFF_AT_FEDEX_LOCATION,
            'oro.fedex.integration.settings.pickup_type.use_scheduled_pickup.label' =>
                FedexIntegrationSettings::PICKUP_USE_SCHEDULED_PICKUP,
        ];
    }

    private function getChoices(): array
    {
        return [
            'oro.fedex.integration.settings.pickup_type.regular.label' =>
                FedexIntegrationSettings::PICKUP_TYPE_REGULAR,
            'oro.fedex.integration.settings.pickup_type.request_courier.label' =>
                FedexIntegrationSettings::PICKUP_TYPE_REQUEST_COURIER,
            'oro.fedex.integration.settings.pickup_type.drop_box.label' =>
                FedexIntegrationSettings::PICKUP_TYPE_DROP_BOX,
            'oro.fedex.integration.settings.pickup_type.business_service_center.label' =>
                FedexIntegrationSettings::PICKUP_TYPE_BUSINESS_SERVICE_CENTER,
            'oro.fedex.integration.settings.pickup_type.station.label' =>
                FedexIntegrationSettings::PICKUP_TYPE_STATION,
        ];
    }
}
