<?php

namespace Oro\Bundle\FedexShippingBundle\Form\Type;

use Oro\Bundle\FedexShippingBundle\Entity\FedexIntegrationSettings;
use Oro\Bundle\FedexShippingBundle\Entity\FedexShippingService;
use Oro\Bundle\FormBundle\Form\Type\OroEncodedPlaceholderPasswordType;
use Oro\Bundle\LocaleBundle\Form\Type\LocalizedFallbackValueCollectionType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\Exception\AccessException;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class FedexIntegrationSettingsType extends AbstractType
{
    const BLOCK_PREFIX = 'oro_fedex_settings';

    /**
     * {@inheritDoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add(
                'labels',
                LocalizedFallbackValueCollectionType::class,
                [
                    'label'    => 'oro.fedex.integration.settings.labels.label',
                    'required' => true,
                    'options'  => ['constraints' => [new NotBlank()]],
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
                    'required' => true
                ]
            )
            ->add(
                'password',
                OroEncodedPlaceholderPasswordType::class,
                [
                    'label' => 'oro.fedex.integration.settings.password.label',
                    'required' => true
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
                'meterNumber',
                TextType::class,
                [
                    'label' => 'oro.fedex.integration.settings.meter_number.label',
                    'required' => true
                ]
            )
            ->add(
                'pickupType',
                ChoiceType::class,
                [
                    'label' => 'oro.fedex.integration.settings.pickup_type.label',
                    'required' => true,
                    'choices' => [
                        FedexIntegrationSettings::PICKUP_TYPE_REGULAR =>
                            'oro.fedex.integration.settings.pickup_type.regular.label',
                        FedexIntegrationSettings::PICKUP_TYPE_REQUEST_COURIER =>
                            'oro.fedex.integration.settings.pickup_type.request_courier.label',
                        FedexIntegrationSettings::PICKUP_TYPE_DROP_BOX =>
                            'oro.fedex.integration.settings.pickup_type.drop_box.label',
                        FedexIntegrationSettings::PICKUP_TYPE_BUSINESS_SERVICE_CENTER =>
                            'oro.fedex.integration.settings.pickup_type.business_service_center.label',
                        FedexIntegrationSettings::PICKUP_TYPE_STATION =>
                            'oro.fedex.integration.settings.pickup_type.station.label',
                    ]
                ]
            )
            ->add(
                'unitOfWeight',
                ChoiceType::class,
                [
                    'label' => 'oro.fedex.integration.settings.unit_of_weight.label',
                    'required' => true,
                    'choices' => [
                        FedexIntegrationSettings::UNIT_OF_WEIGHT_LB =>
                            'oro.fedex.integration.settings.unit_of_weight.lb.label',
                        FedexIntegrationSettings::UNIT_OF_WEIGHT_KG =>
                            'oro.fedex.integration.settings.unit_of_weight.kg.label',
                    ]
                ]
            )
            ->add(
                'shippingServices',
                'entity',
                [
                    'class' => FedexShippingService::class,
                    'property' => 'description',
                    'label' => 'oro.fedex.integration.settings.shipping_services.label',
                    'required' => true,
                    'multiple' => true,
                ]
            );
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
}
