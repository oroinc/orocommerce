<?php

namespace Oro\Bundle\UPSBundle\Form\Type;

use Oro\Bundle\AddressBundle\Form\Type\CountryType;
use Oro\Bundle\IntegrationBundle\Provider\TransportInterface;
use Oro\Bundle\ShippingBundle\Provider\ShippingOriginProvider;
use Oro\Bundle\UPSBundle\Entity\UPSTransport;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class UPSTransportSettingsType extends AbstractType
{
    const NAME = 'oro_ups_transport_settings_type';

    /**
     * @var TransportInterface
     */
    protected $transport;

    /**
     * @var ShippingOriginProvider
     */
    protected $shippingOriginProvider;

    /**
     * @var string
     */
    protected $dataClass;

    /**
     * @param TransportInterface $transport
     * @param ShippingOriginProvider $shippingOriginProvider
     */
    public function __construct(
        TransportInterface $transport,
        ShippingOriginProvider $shippingOriginProvider
    ) {
        $this->transport  = $transport;
        $this->shippingOriginProvider = $shippingOriginProvider;
    }

    /**
     * @param string $dataClass
     */
    public function setDataClass($dataClass)
    {
        $this->dataClass = $dataClass;
    }

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder->add(
            'baseUrl',
            'text',
            ['label' => 'oro.ups.transport.base_url.label', 'required' => true]
        );
        $builder->add(
            'apiUser',
            'text',
            ['label' => 'oro.ups.transport.api_user.label', 'required' => true]
        );
        $builder->add(
            'apiPassword',
            'password',
            [
                'label'       => 'oro.ups.transport.api_password.label',
                'required'    => true,
                'constraints' => [new NotBlank()]
            ]
        );
        $builder->add(
            'apiKey',
            'text',
            [
                'label'       => 'oro.ups.transport.api_key.label',
                'required'    => true,
                'constraints' => [new NotBlank()]
            ]
        );
        $builder->add(
            'shippingAccountName',
            'text',
            [
                'label' => 'oro.ups.transport.shipping_account_name.label',
                'required' => true,
                'constraints' => [new NotBlank()]
            ]
        );
        $builder->add(
            'shippingAccountNumber',
            'text',
            [
                'label' => 'oro.ups.transport.shipping_account_number.label',
                'required' => true,
                'constraints' => [new NotBlank()]
            ]
        );
        $builder->add(
            'country',
            CountryType::class,
            [
                'label' => 'oro.ups.transport.country.label',
                'required' => true,
                'constraints' => [new NotBlank()],
            ]
        );
        $builder->add(
            'applicableShippingServices',
            'entity',
            [
                'label' => 'oro.ups.transport.shipping_service.plural_label',
                'required' => true,
                'mapped' => true,
                'multiple' => true,
                'class' => 'Oro\Bundle\UPSBundle\Entity\ShippingService',
            ]
        );
        $builder->addEventListener(FormEvents::PRE_SET_DATA, function (FormEvent $event) {
            /** @var UPSTransport $transport */
            $transport = $event->getData();
            $form = $event->getForm();

            if (!$transport || null === $transport->getCountry()) {
                $country = $this
                    ->shippingOriginProvider
                    ->getSystemShippingOrigin()
                    ->getCountry();
                
                $form->remove('country');
                $form->add(
                    'country',
                    CountryType::class,
                    [
                        'label' => 'oro.ups.transport.country.label',
                        'required' => true,
                        'constraints' => [new NotBlank()],
                        'data' => $country
                    ]
                );
            }
        });
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => $this->dataClass ?: $this->transport->getSettingsEntityFQCN()
        ]);
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return $this->getBlockPrefix();
    }

    /**
     * {@inheritdoc}
     */
    public function getBlockPrefix()
    {
        return self::NAME;
    }
}
