<?php

namespace OroB2B\Bundle\CustomerBundle\Form\Type;

use Oro\Bundle\AddressBundle\Form\EventListener\FixAddressesPrimarySubscriber;

use OroB2B\Bundle\CustomerBundle\Form\EventListener\FixCustomerAddressesDefaultSubscriber;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolverInterface;

class CustomerTypedAddressType extends AbstractType
{
    const NAME = 'orob2b_customer_typed_address';

    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        if ($options['single_form'] && $options['all_addresses_property_path']) {
            $builder->addEventSubscriber(
                new FixAddressesPrimarySubscriber($options['all_addresses_property_path'])
            );
            $builder->addEventSubscriber(
                new FixCustomerAddressesDefaultSubscriber($options['all_addresses_property_path'])
            );
        }

        $builder
            ->add(
                'types',
                'translatable_entity',
                [
                    'class'    => 'OroAddressBundle:AddressType',
                    'property' => 'label',
                    'required' => false,
                    'multiple' => true,
                    'expanded' => true
                ]
            )
            ->add(
                'defaults',
                'orob2b_customer_typed_address_with_default',
                [
                    'class'    => 'OroAddressBundle:AddressType',
                    'required' => false,
                ]
            )
            ->add(
                'primary',
                'checkbox',
                [
                    'required' => false
                ]
            );
    }

    public function setDefaultOptions(OptionsResolverInterface $resolver)
    {
        $resolver->setDefaults(
            [
                'data_class' => 'OroB2B\Bundle\CustomerBundle\Entity\CustomerAddress',
                'single_form' => true,
                'all_addresses_property_path' => 'owner.addresses'
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
        return self::NAME;
    }
}
