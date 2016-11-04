<?php

namespace Oro\Bundle\CustomerBundle\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

use Oro\Bundle\AddressBundle\Form\EventListener\FixAddressesPrimarySubscriber;
use Oro\Bundle\CustomerBundle\Form\EventListener\FixAccountAddressesDefaultSubscriber;

class FrontendAccountTypedAddressType extends AbstractType
{
    const NAME = 'oro_account_frontend_typed_address';

    /** @var string */
    protected $dataClass;

    /** @var string */
    protected $addressTypeDataClass;

    /**
     * {@inheritdoc}
     */
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($options['single_form'] && $options['all_addresses_property_path']) {
            $builder->addEventSubscriber(
                new FixAddressesPrimarySubscriber($options['all_addresses_property_path'])
            );
            $builder->addEventSubscriber(
                new FixAccountAddressesDefaultSubscriber($options['all_addresses_property_path'])
            );
        }

        $builder
            ->add(
                'types',
                'translatable_entity',
                [
                    'class'    => $this->addressTypeDataClass,
                    'property' => 'label',
                    'required' => false,
                    'multiple' => true,
                    'expanded' => true
                ]
            )
            ->add(
                'frontendOwner',
                FrontendAccountSelectType::NAME,
                [
                    'label' => 'oro.customer.account.entity_label',
                    'required' => false,
                    'mapped' => false
                ]
            )
            ->add(
                'defaults',
                AccountTypedAddressWithDefaultType::NAME,
                [
                    'class'    => $this->addressTypeDataClass,
                    'required' => false,
                ]
            )
            ->add(
                'primary',
                'checkbox',
                [
                    'required' => false
                ]
            )
            ->add(
                'phone',
                'text',
                [
                    'required' => false
                ]
            );
    }

    /**
     * {@inheritdoc}
     */
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => $this->dataClass,
                'single_form' => true,
                'all_addresses_property_path' => 'frontendOwner.addresses',
                'ownership_disabled' => true
            ]
        );
    }

    /**
     * {@inheritdoc}
     */
    public function getParent()
    {
        return 'oro_address';
    }

    /**
     * {@inheritdoc}
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
        return static::NAME;
    }

    /**
     * @param string $dataClass
     */
    public function setDataClass($dataClass)
    {
        $this->dataClass = $dataClass;
    }

    /**
     * @param string $addressTypeDataClass
     */
    public function setAddressTypeDataClass($addressTypeDataClass)
    {
        $this->addressTypeDataClass = $addressTypeDataClass;
    }
}
