<?php

namespace Oro\Bundle\UPSBundle\Form\Type;

use Oro\Bundle\IntegrationBundle\Provider\TransportInterface;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

class UPSTransportSettingFormType extends AbstractType
{
    const NAME = 'oro_ups_transport_setting_form_type';

    /**
     * @var TransportInterface
     */
    protected $transport;

    /**
     * @var string
     */
    protected $dataClass;

    /**
     * @param TransportInterface $transport
     */
    public function __construct(
        TransportInterface $transport
    ) {
        $this->transport  = $transport;
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
            'applicableShippingServices',
            UPSShippingServiceCollectionType::NAME,
            [
                'label' => 'oro.ups.transport.shipping_service.plural_label',
                'required' => true,
                'mapped' => true
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function setDefaultOptions(OptionsResolverInterface $resolver)
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
